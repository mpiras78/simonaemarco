# Partecipazione di Nozze — Simona & Marco

Sito web a tema invito di matrimonio, pensato per essere condiviso tramite
un link (es. WhatsApp). Tutta la grafica è gestita da CSS, quindi facilmente
personalizzabile senza toccare l'HTML.

## Struttura del progetto

```
partecipazione-nozze/
├── index.html          -> tutta la struttura del sito (busta + sito single-page)
├── css/
│   └── style.css       -> TUTTO lo stile (colori, font, immagini) tramite variabili CSS
├── js/
│   └── script.js       -> animazione busta, countdown, apertura form, invio AJAX
├── php/
│   └── send_rsvp.php   -> riceve il form RSVP e invia una email di conferma
├── images/
│   └── LEGGIMI.txt     -> istruzioni su quali foto aggiungere (hero-bg.jpg, coppia.jpg)
└── README.md
```

## Come funziona

1. **Schermata iniziale**: busta da lettera chiusa con ceralacca, con i nomi
   degli sposi e il pulsante "Tocca per aprire". Cliccando parte
   un'animazione (la ceralacca si rompe, la falda si apre, la lettera esce)
   e si passa al sito vero e proprio.
2. **Sito single-page**, diviso in 4 sezioni scorrevoli:
   - **Hero**: foto di sfondo, nomi in grande, data e countdown
     (giorni/ore/minuti/secondi) fino al 04/07/2027 alle 17:30.
   - **Invito**: messaggio standard, foto della coppia, illustrazione di
     due calici di spumante (SVG, nessuna immagine esterna necessaria).
   - **Dove e quando**: illustrazione stilizzata di un agriturismo (SVG),
     nome "Agriturismo Le Giuggiole", data/ora, indirizzo, mappa Google Maps
     incorporata + pulsante "Apri in Google Maps".
   - **RSVP**: domanda "Sarete con noi?", testo su sfondo bianco stile
     lettera, pulsante che apre un form a tutto schermo con:
     nome e cognome, numero totale ospiti, numero bambini, scelta
     partecipo/non partecipo/non so ancora, restrizioni alimentari
     (vegetariano, vegano, senza glutine, senza lattosio + altre allergie
     libere), messaggio per gli sposi, invio tramite PHP.

## Personalizzazione grafica (css/style.css)

Tutto si controlla dalle variabili in cima al file `css/style.css`:

```css
:root {
  --color-primary: #8a6d3b;   /* colore principale (oro/bronzo) */
  --color-cream: #faf6ee;     /* sfondo crema */
  --font-script: 'Great Vibes', cursive;   /* font nomi */
  --font-serif: 'Cormorant Garamond', serif; /* font testi */
  --img-hero-bg: url('../images/hero-bg.jpg');
  --img-couple-photo: url('../images/coppia.jpg');
  ...
}
```

Cambia questi valori per modificare colori, font o immagini in tutto il
sito, senza toccare l'HTML.

## Immagini da aggiungere

Vedi `images/LEGGIMI.txt`. In sintesi:
- `images/hero-bg.jpg` → foto di sfondo della sezione iniziale (dopo la busta)
- `images/coppia.jpg` → foto della coppia nella sezione "Invito"

Il resto (busta, ceralacca, calici, agriturismo) è disegnato via CSS/SVG,
quindi già pronto e leggero.

## Data, ora e location

- Data evento: **04/07/2027 alle 17:30** — impostata sia nel testo
  (`index.html`) sia nel countdown (`js/script.js`, variabile `weddingDate`).
- Location: **Agriturismo Le Giuggiole**, Via della Fontana Corvia 80,
  00132 Roma (RM). La mappa incorporata e il pulsante "Apri in Google Maps"
  usano già questo indirizzo.

Per cambiare data/location in futuro, modifica:
1. `index.html`: testi delle sezioni Hero e "Dove e quando"
2. `js/script.js`: `const weddingDate = new Date('2027-07-04T17:30:00');`
3. `index.html`: l'URL dell'iframe Google Maps e del pulsante "Apri in Google Maps"

## Form RSVP e invio email (PHP)

Il form invia i dati via `fetch` (AJAX, senza ricaricare la pagina) a
`php/send_rsvp.php`, che:
- valida i campi obbligatori (nome, numero ospiti, scelta partecipazione)
- invia una email a **goemontero@gmail.com** con tutti i dettagli
- se l'invio email fallisce (es. hosting senza `mail()` configurata),
  salva comunque una copia in `php/rsvp_log.txt` come backup, così nessuna
  risposta viene persa
- risponde in JSON, mostrando un messaggio di conferma/errore nel form

### Requisiti

- Il sito **deve essere ospitato su un server con supporto PHP** (la
  funzione `mail()` di PHP viene usata per inviare le notifiche).
  Aprire `index.html` direttamente dal proprio computer (file://) non
  farà funzionare l'invio del form: serve un vero hosting/server web.
- Molti hosting condivisi economici (es. Aruba, SiteGround, Altervista,
  ecc.) supportano `mail()` senza configurazione aggiuntiva. Se invece la
  tua email di destinazione non riceve nulla, verifica con il tuo provider
  di hosting se `mail()` è abilitata, oppure valuta di sostituirla con
  l'invio tramite SMTP (es. libreria PHPMailer) usando le credenziali del
  tuo provider email.

### Cambiare l'indirizzo email di destinazione

Modifica questa riga in `php/send_rsvp.php`:

```php
const TO_EMAIL = 'goemontero@gmail.com';
```

## Come pubblicare e condividere il link

1. Carica l'intera cartella `partecipazione-nozze` sul tuo hosting (via FTP
   o pannello di controllo), mantenendo la struttura delle sottocartelle.
2. Verifica che il sito sia raggiungibile, es.
   `https://tuodominio.it/partecipazione-nozze/index.html`
   (o come index principale del dominio/sottodominio).
3. Testa il form RSVP compilandolo tu stesso, per verificare che l'email
   arrivi correttamente all'indirizzo configurato.
4. Copia il link e invialo agli invitati via WhatsApp. Al primo tocco
   vedranno la busta chiusa; toccando "Tocca per aprire" si animerà e
   apparirà il sito completo.

## Note tecniche

- Font "Great Vibes", "Cormorant Garamond" e "Cinzel" caricati da Google
  Fonts (richiede connessione internet per visualizzarli al meglio; in
  assenza di connessione il browser userà un font serif di sistema).
- Nessuna dipendenza da framework esterni: solo HTML, CSS e JavaScript
  puro (vanilla), più un semplice script PHP lato server.
- Il sito è responsive: si adatta automaticamente a smartphone, tablet e
  desktop.
