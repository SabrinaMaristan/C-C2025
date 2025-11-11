// 
  const params = new URLSearchParams(window.location.search);
  if (params.has("timeout")) {
    Swal.fire({
      icon: "info",
      title: "Sesión expirada",
      text: "Tu sesión fue cerrada por inactividad. Vuelve a iniciar sesión.",
      confirmButtonText: "Aceptar"
    });
  }