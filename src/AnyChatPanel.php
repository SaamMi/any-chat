<?
namespace SaamMi\AnyChat;

use Illuminate\Support\Facades\Route;
use Livewire\Wireable;

class AnyChatPanel
{

     protected string $id;
    protected string $path;
    protected bool $hasEmojiPicker = false;
    protected bool $hasFileUploads = false;
    protected string $primaryColor = '#2563eb';
    protected string $mode = 'stateless';
    protected bool $hasAuth = false;

    
    

    public function __construct(string $id) 
    {
        $this->id = $id;
        $this->path = '/' . $id;
          

    }

    public static function make(string $id): self
    {
        return new static($id);
    }

    public function path(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Fluent method to enable stateful persistence
     */
    public function allowState(bool $condition = true): self
    {
        $this->mode = $condition ? 'stateful' : 'stateless';
        return $this;
    }

    public function allowEmojis(bool $condition = true): self
    {
        $this->hasEmojiPicker = $condition;
        return $this;
    }

    public function allowFileUploads(bool $condition = true): self
    {
        $this->hasFileUploads = $condition;
        return $this;
    }
   public function allowAuth(bool $condition = true): self
    {
        $this->hasAuth = $condition;
        return $this;
    }
    public function primaryColor(string $color): self
    {
        $this->primaryColor = $color;
        return $this;
    }

    /**
     * Registers the route and returns the configuration
     */
    /**
     * Registers the route and returns the configuration
     */
    public function register(): void
    {
        $config = [
            'id' => $this->id,
            'emojis' => $this->hasEmojiPicker,
            'uploads' => $this->hasFileUploads,
            'color' => $this->primaryColor,
            'persistenceMode' => $this->mode,
            'auth' => $this->hasAuth,
        ];
//dd($this-hasAuth);
        $middleware = ['web']; 
    
    if ($this->hasAuth) {
        $middleware[] = 'auth';
    }

        // Map the route directly to the Livewire component.
        // Optional parameters handle both the /anychat (index) and /anychat/{id}/{type} (show) routes.
        Route::get($this->path . '/{chatId?}/{type?}', \SaamMi\AnyChat\Livewire\PublicResponse::class)
            ->defaults('config', $config)
            ->middleware($middleware)
            ->name("anychat.panel.{$this->id}");
    }


}
