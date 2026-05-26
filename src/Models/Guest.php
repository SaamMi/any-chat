<?
namespace SaamMi\AnyChat\Models;

use Illuminate\Database\Eloquent\Model;
use SaamMi\AnyChat\Traits\InteractsWithConversations;

class Guest extends Model
{
    use InteractsWithConversations;

    // Use the hexstring as the primary key
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id'];
}