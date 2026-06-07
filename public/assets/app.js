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

function applyPhoneMask(input) {
  input.addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '').substring(0, 11);
    if (v.length > 7) {
      v = '(' + v.substring(0, 2) + ')' + v.substring(2, 7) + '-' + v.substring(7);
    } else if (v.length > 2) {
      v = '(' + v.substring(0, 2) + ')' + v.substring(2);
    } else if (v.length > 0) {
      v = '(' + v;
    }
    this.value = v;
  });
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-mask="cpf"]').forEach(applyCpfMask);
  document.querySelectorAll('[data-mask="phone"]').forEach(applyPhoneMask);

  var managedModals = [];

  function closeAllModals() {
    managedModals.forEach(function (modal) {
      modal.classList.remove('is-open');
    });
  }

  function setupModal(config) {
    var modal = document.querySelector(config.modalSelector);
    if (!modal) return;

    managedModals.push(modal);

    function close() {
      modal.classList.remove('is-open');
    }

    function open(button) {
      if (typeof config.beforeOpen === 'function') {
        config.beforeOpen(button, modal);
      }
      modal.classList.add('is-open');
    }

    document.querySelectorAll(config.triggerSelector).forEach(function (button) {
      button.addEventListener('click', function () {
        open(this);
      });
    });

    modal.querySelectorAll('[data-close-modal]').forEach(function (button) {
      button.addEventListener('click', close);
    });

    modal.addEventListener('click', function (event) {
      if (event.target === modal) close();
    });
  }

  setupModal({
    modalSelector: '[data-role-modal]',
    triggerSelector: '.js-open-role-modal',
    beforeOpen: function (button, modal) {
      var roleIdInput = modal.querySelector('[data-role-id-input]');
      var roleLabelInput = modal.querySelector('[data-role-label-input]');
      roleIdInput.value = button.dataset.roleId || '';
      roleLabelInput.value = button.dataset.roleLabel || '';
      roleLabelInput.focus();
      roleLabelInput.select();
    }
  });

  setupModal({
    modalSelector: '[data-create-role-modal]',
    triggerSelector: '.js-open-create-role-modal'
  });

  setupModal({
    modalSelector: '[data-create-user-modal]',
    triggerSelector: '.js-open-create-user-modal'
  });

  setupModal({
    modalSelector: '[data-edit-user-modal]',
    triggerSelector: '.js-open-edit-user-modal',
    beforeOpen: function (button, modal) {
      modal.querySelector('[data-edit-user-id]').value = button.dataset.userId || '';
      modal.querySelector('[data-edit-user-name]').value = button.dataset.userName || '';
      modal.querySelector('[data-edit-user-username]').value = button.dataset.userUsername || '';
      modal.querySelector('[data-edit-user-role]').value = button.dataset.userRole || '';
      modal.querySelector('[data-edit-user-active]').value = button.dataset.userActive || '1';
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeAllModals();
  });
});
