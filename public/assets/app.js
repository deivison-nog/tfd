function confirmDelete() {
  return window.confirm('Tem certeza que deseja excluir este registro?');
}

function applyCpfMask(input) {
  input.addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '').substring(0, 11);
    v = v.replace(/^(\d{3})(\d)/, '$1.$2');
    v = v.replace(/^(\d{3}\.\d{3})(\d)/, '$1.$2');
    v = v.replace(/^(\d{3}\.\d{3}\.\d{3})(\d)/, '$1-$2');
    this.value = v;
  });
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-mask="cpf"]').forEach(applyCpfMask);
});
