<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'subject_type',
        'subject_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'ip_address',
        'user_agent',
        'payload',
        'result',
        'severity',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    protected function eventLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => config("audit.events.{$this->event_type}", $this->event_type),
        );
    }

    protected function actorLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->actor_name
                ?? ($this->actor_type && $this->actor_id ? class_basename($this->actor_type) . " #{$this->actor_id}" : 'Hệ thống'),
        );
    }

    public function scopeEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeSubjectType(Builder $query, string $subjectType): Builder
    {
        return $query->where('subject_type', $subjectType);
    }

    public function scopeSubject(Builder $query, string $subjectType, int $subjectId): Builder
    {
        return $query->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId);
    }
}
