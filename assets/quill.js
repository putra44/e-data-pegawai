var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
        toolbar: '#toolbar'
    }
});

document.querySelector('form').onsubmit = function () {
    document.getElementById('isi').value = quill.root.innerHTML;
};