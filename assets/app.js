/*
 * Arquivo: app.js
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: melhorias opcionais de interface (o sistema funciona normalmente sem JavaScript).
 *  - select[data-alternar]: mostra só os campos do tipo escolhido (elementos com data-grupo / data-valor);
 *  - form[data-confirmar]: pede confirmação antes de enviar (ex.: remover, cancelar).
 */
(function () {
  'use strict';

  function alternar(select) {
    var grupo = select.getAttribute('data-alternar');
    var escolhido = select.value;
    var elementos = document.querySelectorAll('[data-grupo="' + grupo + '"]');
    Array.prototype.forEach.call(elementos, function (elemento) {
      var valores = (elemento.getAttribute('data-valor') || '').split(' ');
      var visivel = valores.indexOf(escolhido) !== -1;
      elemento.hidden = !visivel;
      Array.prototype.forEach.call(elemento.querySelectorAll('input, select, textarea'), function (campo) {
        campo.disabled = !visivel;
      });
    });
  }

  Array.prototype.forEach.call(document.querySelectorAll('select[data-alternar]'), function (select) {
    select.addEventListener('change', function () { alternar(select); });
    alternar(select);
  });

  Array.prototype.forEach.call(document.querySelectorAll('form[data-confirmar]'), function (formulario) {
    formulario.addEventListener('submit', function (evento) {
      if (!window.confirm(formulario.getAttribute('data-confirmar'))) {
        evento.preventDefault();
      }
    });
  });
})();
