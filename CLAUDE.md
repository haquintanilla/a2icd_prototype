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
  - **Excepción temporal:** las 5 fotos del carrete de Proyectos son fotos provisionales de Unsplash (licencia gratuita) enlazadas desde `images.unsplash.com`. Cuando el usuario mande fotos de proyectos de A2ICD, guárdalas en `img/proyectos/` y usa rutas relativas.
- Diseño responsivo: debe verse bien en celular.
- Contenido en español (México).

## Tipografía
- Salvo que el usuario indique lo contrario, todo el sitio usa **IBM Plex Sans**:
  - **Títulos** (h1 a h6): Semi Bold (**600**).
  - **Navegación** (menús y sus enlaces): Medium (**500**).
  - **Párrafos** y texto general: Regular (**400**).
  - **Excepción pedida por el usuario:** la descripción de la empresa en el footer (`.pie-descripcion`) usa Light (**300**).
- Cárgala desde Google Fonts, solo con esos cuatro pesos (300, 400, 500 y 600), dentro del `<head>`:
  ```html
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  ```
- Fuentes de respaldo: `font-family: "IBM Plex Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;`

## Seguridad
- Este repositorio es **público**: nunca guardes contraseñas, claves de API ni datos privados.

## Antes de cada cambio
- Revisa que la página funcione y explica al usuario qué cambió antes de subirlo.
