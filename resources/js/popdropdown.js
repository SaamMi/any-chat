// js/popdropdown.js
var POPDropdown = class extends HTMLElement {

  constructor() {
        super()

        this.boot?.()
    }

   boot() {

  let trigger = this.trigger()

  let overlay = this.querySelector('[popover]');


        if (! trigger) {
            return console.warn('ui-popdropdown: no trigger element found', this)
        } else if (! overlay) {
            return console.warn('ui-popdropdown: no [popover] overlay found', this)
        }
//  this._disabled = this.hasAttribute('disabled')


trigger.addEventListener('click',() => {
             // Use the built-in Popover API methods on the content element
              overlay.togglePopover(); });
            
 

}
 trigger() {
        return this.querySelector('button')
    }

    connectedCallback() {
        // Called when the element is inserted into the DOM
      //  this.innerHTML = `<p>Hello from my custom element!</p>`;

        //this.boot();
      }


   overlay() {
        return this.lastElementChild?.matches('[popover]') && this.lastElementChild
    }


}
//element("popdropdown", POPDropdown);

 customElements.define('ui-popdropdown',POPDropdown)

//window.POPDropdown = POPDropdown;
