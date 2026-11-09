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

## Seguridad
- Este repositorio es **público**: nunca guardes contraseñas, claves de API ni datos privados.

## Antes de cada cambio
- Revisa que la página funcione y explica al usuario qué cambió antes de subirlo.
