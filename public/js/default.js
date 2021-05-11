var shiftWindow = function() {
    var scroll = -document.getElementById('id_navbar').clientHeight - 30;
    console.log(scroll);
    scrollBy(0, scroll);
};
window.addEventListener("hashchange", shiftWindow);

function load() { if (window.location.hash) shiftWindow(); }