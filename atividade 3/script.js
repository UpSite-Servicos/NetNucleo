// SISGED - script.js
// Confirmações de exclusão, validação de formulários e feedback de horário.

document.addEventListener('DOMContentLoaded', function () {

  // Confirmação antes de qualquer ação de exclusão (links/botões com data-confirm)
  document.querySelectorAll('[data-confirm]').forEach(function (elemento) {
    elemento.addEventListener('click', function (evento) {
      const mensagem = elemento.getAttribute('data-confirm') || 'Tem certeza que deseja excluir este registro?';
      if (!confirm(mensagem)) {
        evento.preventDefault();
      }
    });
  });

  // Validação de formulários Bootstrap (feedback visual de campos obrigatórios)
  document.querySelectorAll('form.precisa-validacao').forEach(function (form) {
    form.addEventListener('submit', function (evento) {
      if (!form.checkValidity()) {
        evento.preventDefault();
        evento.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  // Na tela de aulas: aviso simples se hora_fim <= hora_inicio (checagem final é sempre no servidor)
  const horaInicio = document.getElementById('hora_inicio');
  const horaFim = document.getElementById('hora_fim');
  const avisoHorario = document.getElementById('aviso-horario');

  function validarIntervaloHorario() {
    if (!horaInicio || !horaFim || !avisoHorario) return;
    if (horaInicio.value && horaFim.value && horaFim.value <= horaInicio.value) {
      avisoHorario.classList.remove('d-none');
    } else {
      avisoHorario.classList.add('d-none');
    }
  }

  if (horaInicio && horaFim) {
    horaInicio.addEventListener('change', validarIntervaloHorario);
    horaFim.addEventListener('change', validarIntervaloHorario);
  }

  // Fecha alertas automaticamente após alguns segundos
  document.querySelectorAll('.alert-auto-fechar').forEach(function (alerta) {
    setTimeout(function () {
      alerta.classList.add('fade');
      setTimeout(() => alerta.remove(), 500);
    }, 5000);
  });
});
