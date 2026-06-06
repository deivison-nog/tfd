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

  var roleModal = document.querySelector('[data-role-modal]');
  if (!roleModal) return;

  var roleIdInput = roleModal.querySelector('[data-role-id-input]');
  var roleLabelInput = roleModal.querySelector('[data-role-label-input]');

  function closeRoleModal() {
    roleModal.classList.remove('is-open');
  }

  function openRoleModal(roleId, roleLabel) {
    roleIdInput.value = roleId;
    roleLabelInput.value = roleLabel;
    roleModal.classList.add('is-open');
    roleLabelInput.focus();
    roleLabelInput.select();
  }

  document.querySelectorAll('.js-open-role-modal').forEach(function (button) {
    button.addEventListener('click', function () {
      openRoleModal(this.dataset.roleId || '', this.dataset.roleLabel || '');
    });
  });

  roleModal.querySelectorAll('[data-close-role-modal]').forEach(function (button) {
    button.addEventListener('click', closeRoleModal);
  });

  roleModal.addEventListener('click', function (event) {
    if (event.target === roleModal) closeRoleModal();
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeRoleModal();
  });
});
