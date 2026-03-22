# AnyChat 💬

⚠️ Note: This package is in Beta. Breaking changes to props (like adminColor or height) may occur before the v1.0.0 stable release.




[![Latest Version on Packagist](https://img.shields.io/packagist/v/saammi/any-chat.svg?style=flat-square)](https://packagist.org/packages/saammi/any-chat)
[![Fix PHP Code Style](https://github.com/saammi/any-chat/actions/workflows/pint.yml/badge.svg)](https://github.com/saammi/any-chat/actions/workflows/pint.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

**AnyChat** is a lightweight, stateless Livewire chat widget for Laravel applications. Designed for support-first interactions, it requires **zero authentication** and **zero database migrations**. Just drop it in and start chatting.



---

## 🚀 Features
* **Stateless by Design**: No database overhead. Messages persist in the local session.
* **Livewire Powered**: Real-time interaction out of the box.
* **Fully Customizable**: Control colors, sizes, and heights directly via Blade props.
* **Dark Mode Support**: Automatically adapts to your application's theme.

## 📦 Installation

You can install the package via composer:

```bash
composer require saammi/any-chat


🛠 Usage
Simply place the Livewire component in your Blade layout (usually before the closing </body> tag):

<livewire:anychat-widget />
and <livewire:anychat-dashboard />  on any other component.
 Setup reverb or pusher and you are good to go.


Currently only the userside chat can be customized.


Advanced Customization foruser facing widget

You can customize the widget's appearance using props:

<livewire:anychat-widget 
    height="500px" 
    width="400px" 
    color="zinc" 
    variant="outline" 
    primaryColor="#7c3aed"
    adminColor="#f3f4f6"
/>


🗺 Roadmap (Beta)
[ ] File upload support in chat.

[ ] Notifications for new messages.

[ ] Multi-agent support.

[ ] Persistent history via optional database driver.

[ ] Support for AI agents.


🤝 Contributing
Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

📄 License
The MIT License (MIT). Please see License File for more information.


