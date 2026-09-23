/**
 * Global Ctrl/Cmd+K listener that opens the command palette Livewire
 * component (resources/views/livewire/command-palette.blade.php) by
 * dispatching a browser event it listens for.
 */
document.addEventListener('keydown', (event) => {
    const isCombo = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k';

    if (!isCombo) return;

    event.preventDefault();
    window.dispatchEvent(new CustomEvent('command-palette:toggle'));
});
