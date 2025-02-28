<div class="auth-container">
   <button class="btn btn-auth" id="authBtn">
       <i class="fas fa-key"></i> Authorize
   </button>
   <button class="btn btn-logout" id="logoutBtn">
       <i class="fas fa-sign-out-alt"></i> Logout
   </button>
   <div class="auth-status" id="authStatus"></div>
</div>

<div class="modal" id="authModal">
   <div class="modal-content">
       <div class="modal-header">
           <h3 class="modal-title">Authorization Required</h3>
       </div>
       <div class="input-group">
           <label>Bearer Token:</label>
           <input type="text" class="token-input" id="tokenInput"
                  placeholder="Enter your token">
       </div>
       <div class="modal-footer">
           <button class="btn" id="cancelBtn">Cancel</button>
           <button class="btn btn-auth" id="submitBtn">Authorize</button>
       </div>
   </div>
</div>


<script>
class SecureAuthManager {
   constructor() {
       this.TOKEN_KEY = 'auth_token';
       this.init();
   }

   async init() {
       this.setupElements();
       this.setupEvents();
       await this.checkAuth();
       this.setupEncryption();
   }

   setupElements() {
       this.modal = document.getElementById('authModal');
       this.tokenInput = document.getElementById('tokenInput');
       this.authBtn = document.getElementById('authBtn');
       this.logoutBtn = document.getElementById('logoutBtn');
       this.authStatus = document.getElementById('authStatus');
   }

   setupEvents() {
       this.authBtn.onclick = () => this.showAuth();
       this.logoutBtn.onclick = () => this.logout();
       document.getElementById('submitBtn').onclick = () => this.authorize();
       document.getElementById('cancelBtn').onclick = () => this.hideModal();
   }

   async setupEncryption() {
       this.encryptionKey = await crypto.subtle.generateKey(
           {name: 'AES-GCM', length: 256},
           true,
           ['encrypt', 'decrypt']
       );
   }

   async encryptToken(token) {
       const encoder = new TextEncoder();
       const data = encoder.encode(token);
       const iv = crypto.getRandomValues(new Uint8Array(12));

       const encrypted = await crypto.subtle.encrypt(
           {name: 'AES-GCM', iv},
           this.encryptionKey,
           data
       );

       return {
           token: Array.from(new Uint8Array(encrypted)),
           iv: Array.from(iv)
       };
   }

   async decryptToken(encrypted) {
       try {
           const decrypted = await crypto.subtle.decrypt(
               {name: 'AES-GCM', iv: new Uint8Array(encrypted.iv)},
               this.encryptionKey,
               new Uint8Array(encrypted.token)
           );
           return new TextDecoder().decode(decrypted);
       } catch {
           return null;
       }
   }

   async authorize() {
       const token = this.tokenInput.value.trim();
       if (!token) {
           this.showError('Token required');
           return;
       }

       try {
           const encrypted = await this.encryptToken(token);
           sessionStorage.setItem(this.TOKEN_KEY, JSON.stringify({
               ...encrypted,
               expires: Date.now() + 15 * 60 * 1000
           }));

           this.updateUI(true);
           this.setupFetchInterceptor();
           this.hideModal();
       } catch (error) {
           this.showError('Encryption failed');
       }
   }

   async checkAuth() {
       const data = sessionStorage.getItem(this.TOKEN_KEY);
       if (!data) return;

       const auth = JSON.parse(data);
       if (Date.now() > auth.expires) {
           this.logout();
           return;
       }

       const token = await this.decryptToken(auth);
       if (token) {
           this.updateUI(true);
           this.setupFetchInterceptor();
       }
   }

   logout() {
       sessionStorage.removeItem(this.TOKEN_KEY);
       this.updateUI(false);
       location.reload();
   }

   updateUI(isAuthorized) {
       this.logoutBtn.style.display = isAuthorized ? 'block' : 'none';
       this.authBtn.style.display = isAuthorized ? 'none' : 'block';
       this.authStatus.innerHTML = isAuthorized ?
           '<i class="fas fa-check-circle"></i><span>Authorized</span>' : '';
   }

   setupFetchInterceptor() {
       const originalFetch = window.fetch;
       window.fetch = async (resource, config = {}) => {
           const data = sessionStorage.getItem(this.TOKEN_KEY);
           if (!data) return originalFetch(resource, config);

           const auth = JSON.parse(data);
           if (Date.now() > auth.expires) {
               this.logout();
               return originalFetch(resource, config);
           }

           const token = await this.decryptToken(auth);
           if (!token) {
               this.logout();
               return originalFetch(resource, config);
           }

           config.headers = {
               ...config.headers,
               'Authorization': `Bearer ${token}`,
               'X-CSRF-Token': crypto.randomUUID()
           };

           try {
               const response = await originalFetch(resource, config);
               if (response.status === 401) this.handleUnauthorized();
               return response;
           } catch (error) {
               console.error('Fetch failed:', error);
               throw error;
           }
       };
   }

   showError(message) {
       alert(message);
   }
}

if (window.isSecureContext) {
   document.addEventListener('DOMContentLoaded', () => new SecureAuthManager());
} else {
   alert('HTTPS required for secure operation');
}
</script>
