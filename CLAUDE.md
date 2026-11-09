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
- Salvo que el usuario indique lo contrario, todo el sitio usa **SF Pro Display** (antes era IBM Plex Sans):
  - **Títulos** (h1 a h6): Semi Bold (**600**).
  - **Navegación** (menús y sus enlaces): Medium (**500**).
  - **Párrafos** y texto general: Regular (**400**).
  - **Footer (pedido del usuario):** los textos del footer usan las mismas características del texto del menú del header: mismo tamaño (`var(--menu-letra)`), Medium (**500**) y al 80 % (en blanco, `--footer-texto-menu`, porque el fondo es oscuro); sus enlaces se ponen rojos con el cursor encima y blancos al oprimir, como el menú. **Excepciones:** el nombre de la empresa en rojo y la frase debajo de los íconos de redes conservan su estilo. (Antes la descripción del footer era Light 300; ya no.)
- **No subas los archivos de SF Pro** al repositorio ni al servidor: la licencia de Apple solo permite usarla para maquetas de apps de Apple, no como fuente de un sitio web.
- SF Pro se muestra porque es la fuente del sistema en Mac, iPhone y iPad (`-apple-system`, `BlinkMacSystemFont`). En Windows y Android se usa **Inter** (licencia libre OFL, muy parecida), cargada desde Google Fonts con optical size y los pesos 300 a 600, dentro del `<head>`:
  ```html
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..600&display=swap" rel="stylesheet">
  ```
- Fuentes en este orden: `font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif;` (no pongas `system-ui` antes de Inter: en Windows mostraría Segoe UI).

## Colores
- **Rojo oficial de A2ICD: `#DE0000`** (el rojo del logo). Es el rojo que se debe usar en **todo** el sitio: textos, enlaces, botones, líneas, fondos y efectos al pasar el cursor.
- En el CSS está como la variable `--rojo-a2`. Usa siempre `var(--rojo-a2)`; no escribas otros rojos (`#e40000`, `#e50100`, etc.).

## Seguridad
- Este repositorio es **público**: nunca guardes contraseñas, claves de API ni datos privados.

## Antes de cada cambio
- Revisa que la página funcione y explica al usuario qué cambió antes de subirlo.
