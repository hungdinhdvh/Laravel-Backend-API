<?php
namespace App\Repositories;
use App\Models\User;

class UserRepository extends BaseRepository {
    public function __construct(User $user) {
        parent::__construct($user);
    }

    // Add user-specific methods here
    public function findByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }
}
