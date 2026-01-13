<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['name', 'email', 'password', 'avatar_url', 'cover_url', 'bio', 'date_of_birth', 'gender'];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',

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

    // JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pets()
    {
        return $this->hasMany(Pet::class, 'user_id');
    }
    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class, 'user_id');
    }
    public function likes()
    {
        return $this->hasMany(Like::class, 'user_id');
    }
    public function groupsCreated()
    {
        return $this->hasMany(Group::class, 'created_by');
    }
    public function groupMemberships()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')
            ->using(GroupMember::class)
            ->withPivot('role', 'joined_at');
    }
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants', 'user_id', 'conversation_id');
    }
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id');
    }
    public function followings()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id');
    }
    /**
     * Định nghĩa ID sẽ được lưu vào cột notifiable_id của bảng notifications
     */
    // public function routeNotificationForDatabase()
    // {
    //     return $this->user_id;
    // }
    // App\Models\User.php

    public function notifications()
    {
        // Thêm tham số thứ 3 và 4 để ép Laravel dùng đúng cột user_id làm định danh
        return $this->morphMany(\App\Models\Notification::class, 'notifiable', 'notifiable_type', 'notifiable_id', 'user_id')
            ->orderBy('created_at', 'desc');
    }

    public function customNotifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable', 'notifiable_type', 'notifiable_id', 'user_id')
            ->orderBy('created_at', 'desc');
    }
    public function interactions()
    {
        return $this->hasMany(UserInteraction::class, 'user_id');
    }

    public function followedPets()
    {
        return $this->belongsToMany(Pet::class, 'pet_follows', 'user_id', 'pet_id')
            ->withPivot('followed_at')
            ->withTimestamps();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->user_id)) {
                $model->user_id = (string) Str::uuid();
            }
        });
    }
}
