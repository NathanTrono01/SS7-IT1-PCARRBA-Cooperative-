
function filterProducts() {
    const query = document.getElementById('searchBar').value.toLowerCase();
    const rows = document.querySelectorAll('#productTable tbody tr');

    rows.forEach(row => {
        const productName = row.cells[0].textContent.toLowerCase();
        row.style.display = productName.includes(query) ? '' : 'none';
    });
}

function clearSearch() {
    document.getElementById('searchBar').value = '';
    filterProducts();
    toggleClearIcon();
}

function toggleClearIcon() {
    const searchBar = document.getElementById('searchBar');
    const clearIcon = document.querySelector('.clear-icon');
    clearIcon.style.display = searchBar.value ? 'block' : 'none';
}

function confirmDelete() {
    return confirm('Are you sure you want to delete this item?');
}

function closeAlert() {
    document.getElementById("alert").style.display = "none";
}

document.addEventListener('DOMContentLoaded', () => {
    toggleClearIcon();
});

function sortTable(columnIndex) {
    const table = document.getElementById('productTable');
    const rows = Array.from(table.rows).slice(1);
    const isAscending = table.getAttribute('data-sort-order') === 'asc';
    const direction = isAscending ? 1 : -1;

    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();

        if (!isNaN(aText) && !isNaN(bText)) {
            return direction * (parseFloat(aText) - parseFloat(bText));
        }

        return direction * aText.localeCompare(bText);
    });

    rows.forEach(row => table.tBodies[0].appendChild(row));
    table.setAttribute('data-sort-order', isAscending ? 'desc' : 'asc');

    // Update sort icons
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        const icon = header.querySelector('.sort-icon img');
        if (index === columnIndex) {
            icon.classList.toggle('sort-asc', isAscending);
            icon.classList.toggle('sort-desc', !isAscending);
        } else {
            icon.classList.remove('sort-asc', 'sort-desc');
        }
    });
}