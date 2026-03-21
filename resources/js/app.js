
import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import './popdropdown.js';
import moment from "moment";

 

window.moment = moment;

Livewire.start()
