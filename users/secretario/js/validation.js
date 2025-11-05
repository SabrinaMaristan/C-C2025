// ------------------------------------------------------
// EVENTOS AL CARGAR EL DOCUMENTO
// ------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {

  // ------------------------------------------------------
  // Mostrar alertas según parámetros GET (error o mensaje)
  // ------------------------------------------------------
  const params = new URLSearchParams(window.location.search);

  if (window.Swal && params.has("error")) {
    const error = params.get("error");
    const campo = params.get("campo") || "dato";

    const errores = {
      CamposVacios: {
        title: "Campos vacíos",
        text: "Por favor complete todos los campos.",
      },
      CiInvalida: {
        title: "Cédula inválida",
        text: "Debe tener 8 dígitos.",
      },
      TelefonoInvalido: {
        title: "Teléfono inválido",
        text: "Debe tener 9 dígitos.",
      },
      ContraseniaInvalida: {
        title: "Contraseña inválida",
        text: "Debe tener entre 8 a 20 caracteres, incluir mayúsculas, minúsculas y números.",
      },
      Duplicado: {
        title: "Usuario duplicado",
        text: `Ya existe un usuario con ese ${campo} registrado.`,
      },
    };

    const alerta = errores[error];
    if (alerta)
      Swal.fire({
        icon: "error",
        title: alerta.title,
        text: alerta.text,
        confirmButtonColor: "#d33",
      });
  }

  // Reabrir el modal automáticamente si corresponde
  if (params.get("abrirModal") === "true") {
    const modalEl = document.getElementById("modalUsuario");
    if (modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }
  }

  // Mensajes de éxito
  if (window.Swal && params.has("msg")) {
    const msg = params.get("msg");
    const exitos = {
      InsercionExitosa: "Creación de Usuario Exitosa",
      EdicionExitosa: "¡Edición Exitosa!",
      EliminacionExitosa: "¡Eliminación Exitosa!",
    };
    if (exitos[msg])
      Swal.fire({
        icon: "success",
        title: exitos[msg],
        confirmButtonColor: "rgba(95, 102, 207, 1)",
      });
  }

  // ------------------------------------------------------
  // VALIDACIONES DE FORMULARIOS
  // ------------------------------------------------------

  // ---- EDICIÓN (delegación por seguridad) ----
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.matches('form[id^="editarUsuarioForm"]')) return;

    e.preventDefault();

    const ok = validarFormularioEdicion(form);
    if (!ok) return;

    if (window.Swal) {
      Swal.fire({
        title: '¿Estás seguro?',
        text: '¿Deseas guardar los cambios?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
      }).then((r) => { if (r.isConfirmed) form.submit(); });
    } else {
      if (confirm('¿Deseas guardar los cambios?')) form.submit();
    }
  });

  // ---- CREACIÓN ----
  const formCreacion = document.querySelector('form[action="./agregar-usuario.php"]');
  if (formCreacion) {
    formCreacion.addEventListener('submit', (e) => {
      e.preventDefault();
      const ok = validarFormulario(formCreacion, true);
      if (!ok) return;

      if (window.Swal) {
        Swal.fire({
          title: '¿Crear usuario?',
          text: 'Se agregará un nuevo usuario',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Sí, crear',
          cancelButtonText: 'Cancelar'
        }).then((r) => { if (r.isConfirmed) formCreacion.submit(); });
      } else {
        if (confirm('¿Crear usuario?')) formCreacion.submit();
      }
    });
  }
});

function leer(form, name) {
  const el = form.querySelector(`[name="${name}"]`);
  // si no existe, devuelve string vacío; si existe, devuelve value recortado
  return (el?.value ?? '').toString().trim();
}

function validarFormulario(form, esCreacion = true) {
  const ci = leer(form,'ci_usuario');
  const nombre = leer(form,'nombre_usuario');
  const apellido = leer(form,'apellido_usuario');
  const gmail = leer(form,'gmail_usuario');
  const telefono = leer(form,'telefono_usuario');
  const cargo = leer(form,'cargo_usuario');
  const contrasenia = leer(form,'contrasenia_usuario');


  if (!ci || !nombre || !apellido || !gmail || !telefono || !cargo) {
    alertSwal('Campos incompletos','Todos los campos son obligatorios'); return false;
  }

  if (!/^\d{8}$/.test(ci)) {
    alertSwal('Cédula inválida','La cédula debe tener 8 dígitos'); return false;
  }

  if (!/^\d{9}$/.test(telefono)) {
    alertSwal('Teléfono inválido','El teléfono debe tener 9 dígitos'); return false;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(gmail)) {
    alertSwal('Email inválido','Por favor ingrese un email válido'); return false;
  }

  if (!contrasenia) {
    alertSwal('Contraseña requerida','Debe ingresar una contraseña'); return false;
  }

  if (contrasenia.length < 8 || contrasenia.length > 20) {
    alertSwal('Contraseña inválida','La contraseña debe tener entre 8 y 20 caracteres'); return false;
  }

  const tieneMayus = /[A-Z]/.test(contrasenia);
  const tieneMinus = /[a-z]/.test(contrasenia);
  const tieneNumero = /[0-9]/.test(contrasenia);
  if (!tieneMayus || !tieneMinus || !tieneNumero) {
    alertSwal('Contraseña inválida','La contraseña debe tener: al menos una MAYÚSCULA, una minúscula y un número'); return false;
  }

  return true;
}

function validarFormularioEdicion(form) {
  const ci = leer(form,'ci_usuario');
  const nombre = leer(form,'nombre_usuario');
  const apellido = leer(form,'apellido_usuario');
  const gmail = leer(form,'gmail_usuario');
  const telefono = leer(form,'telefono_usuario');
  const cargo = leer(form,'cargo_usuario');
  // en edición, la contraseña puede venir vacía (no cambiar)
  const contrasenia = leer(form,'contrasenia_usuario');

  console.log('Valores edición:', {ci, nombre, apellido, gmail, telefono, cargo, contrasenia});

  if (!ci || !nombre || !apellido || !gmail || !telefono || !cargo) {
    alertSwal('Campos incompletos','Todos los campos son obligatorios'); return false;
  }

  if (!/^\d{8}$/.test(ci)) {
    alertSwal('Cédula inválida','La cédula debe tener 8 dígitos'); return false;
  }

  if (!/^\d{9}$/.test(telefono)) {
    alertSwal('Teléfono inválido','El teléfono debe tener 9 dígitos'); return false;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(gmail)) {
    alertSwal('Email inválido','Por favor ingrese un email válido'); return false;
  }

  if (contrasenia) {
    if (contrasenia.length < 8 || contrasenia.length > 20) {
      alertSwal('Contraseña inválida','La contraseña debe tener entre 8 y 20 caracteres'); return false;
    }
    const tieneMayus = /[A-Z]/.test(contrasenia);
    const tieneMinus = /[a-z]/.test(contrasenia);
    const tieneNumero = /[0-9]/.test(contrasenia);
    if (!tieneMayus || !tieneMinus || !tieneNumero) {
      alertSwal('Contraseña inválida','La contraseña debe tener: al menos una MAYÚSCULA, una minúscula y un número'); return false;
    }
  }

  return true;
}

// ----------------------------
// Evitar reenvío al recargar
// ----------------------------
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.pathname);
}

function alertSwal(title, text) {
  if (window.Swal) {
    Swal.fire({ icon: 'error', title, text, confirmButtonColor: '#d33' });
  } else {
    alert(`${title}\n${text}`);
  }
}
