<?
namespace SaamMi\AnyChat\Support;

use Livewire\Wireable;

class PanelConfig implements Wireable
{
    public function __construct(
        public string $id,
        public bool $allowEmojis = false,
        public bool $allowUploads = false,
        public string $persistenceMode = 'stateless',
        public string $color = '#2563eb',
    ) {}

    public function toLivewire()
    {
        return [
            'id' => $this->id,
            'allowEmojis' => $this->allowEmojis,
            'allowUploads' => $this->allowUploads,
            'persistenceMode' => $this->persistenceMode,
            'color' => $this->color,
        ];
    }

    public static function fromLivewire($value)
    {
        return new static(
            $value['id'],
            $value['allowEmojis'],
            $value['allowUploads'],
            $value['persistenceMode'],
            $value['color'],
        );
    }
}