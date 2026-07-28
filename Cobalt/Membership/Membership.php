<?php
namespace Cobalt\Membership;

use ArrayAccess;
use Cobalt\Membership\Enums\Platform;
use Cobalt\Membership\Enums\PaymentCadence;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\USDCentsType;
use DateTime;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use MongoDB\BSON\UTCDateTime;
use stdClass;

class Membership implements ArrayAccess, Persistable {
    public Platform $platform;
    public int $cents;
    public bool $is_active;
    public ?DateTime $start_date;
    public ?DateTime $end_date;
    public ?DateTime $next_pledge;
    public PaymentCadence $cadence;

    public function bsonSerialize(): array|stdClass|Document {
        return [
            'platform' => $this->platform,
            'cents' => $this->cents,
            'is_active' => $this->is_active,
            'start_date' => new UTCDateTime($this->start_date),
            'end_date' => new UTCDateTime($this->end_date),
            'next_pledge' => new UTCDateTime($this->next_pledge),
            'cadence' => (string)$this->cadence,
        ];
    }

    public function bsonUnserialize(array $data): void {
        $this->platform = Platform::from($data['platform'] ?? "unknown");
        $this->cents = $data['cents'] ?? 0;
        $this->is_active = $data['is_active'] ?? false;
        $this->start_date = ($data['start_date']) ? $data['start_date']->toDateTime() : null;
        $this->end_date = ($data['end_date']) ? $data['end_date']->toDateTime() : null;
        $this->next_pledge = ($data['next_pledge']) ? $data['next_pledge']->toDateTime() : null;
        $this->cadence = PaymentCadence::from($data['cadence'] ?? "unknown");
    }

    public function nullish() {
        $this->platform = Platform::UNKNOWN;
        $this->cents = 0;
        $this->is_active = false;
        $this->start_date = null;
        $this->end_date = null;
        $this->next_pledge = null;
        $this->cadence = PaymentCadence::UNKNOWN;
    }

    public function offsetExists(mixed $offset): bool {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->{$offset});
    }

}