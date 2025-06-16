// modoOscuro.js
document.addEventListener('DOMContentLoaded', function() {
    // Obtener tema inicial desde PHP (sesión o localStorage)
    const initialTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', initialTheme);
    updateToggleIcon(initialTheme);

    // Manejar clic en el botón de modo oscuro
    document.getElementById('darkModeToggle').addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateToggleIcon(newTheme);

        // Actualizar tema en la base de datos si el usuario está logueado
        fetch('update_theme.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ theme: newTheme })
        }).catch(error => console.error('Error al actualizar tema:', error));
    });

    function updateToggleIcon(theme) {
        const icon = document.querySelector('#darkModeToggle i');
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }
});