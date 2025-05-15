<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Authenticatable
{
    //
    use HasFactory, Notifiable;

    protected $guard = 'admin';

    protected $fillable = [ 
        'name',
        'email',
        'password',
        'gender',
        'status',
        'age',
        'birthday',
        'phone',
        'img',
        'role_id',
        'remember_token',
        'created_by',
        'updated_by',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class , 'role_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class , 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Admin::class , 'updated_by');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
