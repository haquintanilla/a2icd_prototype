# Prototipo del sitio de A2ICD

## Publicación
- Cada push a la rama `main` se publica automáticamente en Hostinger, en **https://prototype.a2icd.com** (carpeta `public_html/prototype` de la cuenta de a2icd.com).
- Publica **solo** en prototype.a2icd.com. Nunca publiques ni modifiques el dominio principal **a2icd.com**: ahí está el sitio WordPress en producción.
- No toques **printlabs.com.mx** ni ningún otro sitio de la cuenta de Hostinger.
- No cambies la configuración de publicación automática (dominio, carpeta o rama) sin confirmación explícita del usuario.

## Tipo de sitio
- Sitio estático: solo HTML, CSS y JavaScript. Sin Node.js y sin paso de compilación.
- La página principal es `index.html`, en la raíz del repositorio.
- Usa rutas relativas para enlaces, imágenes y estilos.
- Diseño responsivo: debe verse bien en celular.
- Contenido en español (México).

## Tipografía
- Salvo que el usuario indique lo contrario, todo el sitio usa **IBM Plex Sans**:
  - **Títulos** (h1 a h6): Semi Bold (**600**).
  - **Navegación** (menús y sus enlaces): Medium (**500**).
  - **Párrafos** y texto general: Regular (**400**).
- Cárgala desde Google Fonts, solo con esos tres pesos, dentro del `<head>`:
  ```html
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  ```
- Fuentes de respaldo: `font-family: "IBM Plex Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;`

## Seguridad
- Este repositorio es **público**: nunca guardes contraseñas, claves de API ni datos privados.

## Antes de cada cambio
- Revisa que la página funcione y explica al usuario qué cambió antes de subirlo.
