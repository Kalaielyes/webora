<?php

class Achievement
{
    public ?int $id;
    public string $title;
    public string $description;
    public string $icon;
    public string $role_type;
    public string $condition_type;
    public float $condition_value;
    public int $points;
    public bool $is_enabled;
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(array $data = [])
    {
        $this->id              = isset($data['id']) ? (int)$data['id'] : null;
        $this->title           = (string)($data['title'] ?? '');
        $this->description     = (string)($data['description'] ?? '');
        $this->icon            = (string)($data['icon'] ?? 'fa-solid fa-star');
        $this->role_type       = (string)($data['role_type'] ?? 'donor');
        $this->condition_type  = (string)($data['condition_type'] ?? 'donation_count');
        $this->condition_value = isset($data['condition_value']) ? (float)$data['condition_value'] : 0.0;
        $this->points          = isset($data['points']) ? (int)$data['points'] : 0;
        $this->is_enabled      = isset($data['is_enabled']) ? (bool)$data['is_enabled'] : true;
        $this->created_at      = isset($data['created_at']) ? (string)$data['created_at'] : null;
        $this->updated_at      = isset($data['updated_at']) ? (string)$data['updated_at'] : null;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'description'     => $this->description,
            'icon'            => $this->icon,
            'role_type'       => $this->role_type,
            'condition_type'  => $this->condition_type,
            'condition_value' => $this->condition_value,
            'points'          => $this->points,
            'is_enabled'      => $this->is_enabled ? 1 : 0,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }

    public function validate(): void
    {
        if ($this->title === '' || mb_strlen($this->title) > 120) {
            throw new InvalidArgumentException('Invalid title');
        }
        if ($this->description === '' || mb_strlen($this->description) > 255) {
            throw new InvalidArgumentException('Invalid description');
        }
    }
}
