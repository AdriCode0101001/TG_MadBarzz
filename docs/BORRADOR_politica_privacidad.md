# Política de Privacidad — Calistenia MadBarzz

> **BORRADOR — REQUIERE REVISIÓN DE UN ABOGADO.**
> Este documento fue redactado por una auditoría técnica automatizada a partir de lo que el
> sistema realmente hace hoy. No es un documento legal terminado, no declara cumplimiento con
> ninguna ley específica, y no debe publicarse en el sitio sin que un abogado lo revise y lo
> ajuste a la normativa boliviana vigente. Los campos entre `[COMPLETAR: ...]` deben ser
> llenados por el propietario del negocio antes de cualquier publicación.

Última actualización: [COMPLETAR: fecha de publicación]

## 1. Quién es el responsable de tus datos

- **Razón social:** [COMPLETAR: razón social registrada del negocio]
- **NIT:** [COMPLETAR]
- **Nombre comercial:** Calistenia MadBarzz
- **Dirección:** Av. Santos Dumont, 4to anillo, al lado de la HOT, Santa Cruz de la Sierra, Bolivia
- **Correo de contacto:** [COMPLETAR: correo de contacto formal para temas de privacidad]
- **WhatsApp:** +591 76372337

Para cualquier consulta sobre tus datos personales, podés escribirnos por WhatsApp o al correo
de contacto indicado arriba.

## 2. Qué datos recolectamos

### 2.1 Datos que nos das al registrarte o al inscribirte

Ya sea que te registrés vos mismo desde el sitio web o que el personal te dé de alta en el
gimnasio, recolectamos:

- Nombre completo, apellido paterno y apellido materno
- Cédula de identidad (CI)
- Teléfono
- Género
- Fecha de nacimiento
- Correo electrónico
- Contraseña (la guardamos protegida con un algoritmo de cifrado unidireccional — ni siquiera
  nuestro personal puede verla)

Estos datos se usan para gestionar tu ficha de socio, tu acceso al sistema y el control de tu
membresía.

**El auto-registro por el sitio web es solo para personas mayores de 18 años.** Si sos menor de
edad, la inscripción la hace el personal presencialmente en el gimnasio — ver la sección
"Menores de edad" más abajo.

### 2.2 Datos biométricos (huella dactilar)

Ver la sección dedicada más abajo — es información sensible y la tratamos aparte.

### 2.3 Datos de uso del servicio

- Registros de asistencia (fecha y hora en que ingresás al gimnasio, marcados por huella o por
  tu carnet QR)
- Historial de pagos que el personal registra manualmente en el sistema (monto, fecha, tipo de
  membresía)
- Rutinas de entrenamiento asignadas por tu entrenador, si aplica

### 2.4 Lo que NO recolectamos

- **No procesamos pagos.** El sistema no es una pasarela de pago y no guarda datos de tarjetas.
  Los pagos se hacen por QR bancario (a la cuenta del gimnasio) o en efectivo, y el sistema solo
  **registra** que el pago ocurrió — no participa en la transacción.
- No usamos cookies de rastreo ni de publicidad, ni herramientas de analítica de terceros en el
  sitio público.

## 3. Datos biométricos (huella dactilar)

Este es un dato sensible y lo tratamos con cuidado especial.

- **Cómo se toma:** un miembro del personal del gimnasio te pide tu huella usando un lector de
  huellas dedicado (ZK9500) conectado a una computadora del gimnasio, presencialmente. No se
  toma tu huella desde el sitio web ni de forma remota.
- **Qué se guarda:** no se guarda una foto o imagen de tu huella. El lector genera una
  **plantilla** (template) — una representación matemática derivada de tu huella — que es lo que
  se almacena en nuestro sistema.
- **Para qué se usa:** exclusivamente para identificarte al ingresar al gimnasio (control de
  asistencia) y confirmar tu membresía vigente.
- **Cómo se protege:** la plantilla se guarda en la base de datos del sistema, en un servidor
  con acceso restringido al personal autorizado, protegido por autenticación. [COMPLETAR: el
  equipo técnico debe confirmar y documentar aquí si se aplicará cifrado adicional a este dato en
  la base de datos — a la fecha de este borrador, no hay cifrado adicional más allá de los
  controles de acceso al servidor.]
- **Alternativa sin huella:** si preferís no dar tu huella, podés ingresar con tu carnet/código
  QR personal en su lugar. Dar la huella es opcional para el control de acceso.
- **Cuánto tiempo se guarda:** mientras tu cuenta esté activa en el sistema.
  [COMPLETAR: el propietario debe definir una política de retención — hoy el sistema no borra
  automáticamente tu huella cuando tu membresía vence o cuando te das de baja; el borrado, si lo
  pedís, se hace manualmente. Ver sección "Tus derechos".]
- **Consentimiento:** antes de tomarte la huella, el personal debe explicarte para qué se usa y
  cómo se guarda, y pedirte tu conformidad. [COMPLETAR: el propietario debe definir un mecanismo
  formal de registro de ese consentimiento — por ejemplo, una constancia firmada o un check en el
  sistema — porque hoy el sistema no guarda un registro explícito de que diste tu consentimiento
  antes de enrolar tu huella.]

## 4. Menores de edad

- El **auto-registro por el sitio web es exclusivamente para personas de 18 años o más.**
- Los **menores de edad son inscritos por el personal, presencialmente**, en el gimnasio — no
  pueden crear su cuenta solos desde la web.
- Para inscribir a un menor, el personal recolecta también los datos de contacto de su
  madre/padre/tutor. [COMPLETAR: el propietario debe definir y el sistema debe implementar qué
  datos del tutor se piden y se guardan — a la fecha de este borrador, el alta de un menor por el
  personal no tiene campos separados para los datos del tutor.]
- **Huella dactilar de un menor:** requiere la autorización expresa de su madre/padre/tutor. Esta
  regla está definida como política del gimnasio; el mecanismo técnico que la haga cumplir dentro
  del sistema (por ejemplo, bloquear el enrolamiento de huella de un menor hasta que quede
  registrado el consentimiento del tutor) está **en desarrollo y todavía no existe en el
  sistema** a la fecha de este borrador. Mientras tanto, el personal debe verificar manualmente
  esa autorización antes de tomar la huella de un menor.
- A los menores que no cuenten con autorización del tutor para dar su huella se les asigna
  únicamente el carnet/código QR para su acceso.

## 5. A quién compartimos tus datos

No vendemos tus datos personales. Los compartimos únicamente con:

| A quién | Qué datos | Para qué |
|---|---|---|
| Backblaze B2 (almacenamiento en la nube) | Copia de respaldo completa de la base de datos, incluida tu ficha personal y tu huella dactilar si la diste | Respaldo del sistema ante fallas o pérdida de datos |
| Telegram (Bot API) | Tu nombre y el estado de tu membresía, solo si vinculaste tu cuenta de Telegram | Enviarte avisos de vencimiento de tu membresía |
| WhatsApp Cloud API (Meta) | Tu nombre, teléfono y el estado de tu membresía | Enviarte avisos de vencimiento por WhatsApp |
| Tu navegador/celular (notificaciones push) | Un identificador técnico de tu dispositivo (no datos personales legibles) | Enviarte notificaciones si activaste esa opción |
| Cloudflare (túnel de red) | Todo el tráfico entre tu navegador y nuestro servidor, en tránsito | Hacer que el sitio sea accesible desde internet de forma segura |

No usamos estos datos para publicidad ni los cedemos a terceros con fines comerciales.

## 6. Tus derechos

Podés pedirnos en cualquier momento:

- **Acceso:** saber qué datos tuyos tenemos guardados.
- **Corrección:** que corrijamos un dato que esté mal.
- **Borrado:** que eliminemos tus datos, incluida tu huella dactilar, cuando ya no seas cliente
  del gimnasio. [COMPLETAR: hoy este borrado se hace manualmente a pedido; el propietario puede
  definir un plazo de respuesta y, más adelante, un proceso automático.]

Para ejercer estos derechos, escribinos por WhatsApp o al correo de contacto indicados en la
sección 1.

## 7. Cambios a esta política

Podemos actualizar esta política si cambia lo que hacemos con tus datos. Te avisaremos por los
mismos medios de contacto que tenemos registrados si el cambio es importante.

---

*Este es un borrador técnico, no un documento legal. [COMPLETAR: reemplazar este pie con la
validación de un abogado antes de publicar.]*
