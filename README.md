# Esportazione email MiC Card

Applicazione web interna in PHP 8.x per estrarre dal database MySQL `store` le email dei clienti MiC Card che hanno dato il consenso newsletter, ed esportarle in Excel.

## Caratteristiche

- Accesso con le credenziali aziendali (Active Directory)
- Ricerca per periodo di registrazione, con date di inizio e fine incluse
- Una sola tabella di risultati, con filtro per consenso: "Solo newsletter" oppure "Newsletter + iniziative culturali"
- Ricerca testuale nell'elenco, caricato pagina per pagina dal server
- Download Excel (.xls) che contiene esattamente l'elenco filtrato mostrato a schermo
- Limite di 65.535 righe per file Excel (oltre, restringere periodo o ricerca)
- Tema chiaro e scuro automatico, secondo le impostazioni del sistema
- Nessuna dipendenza da CDN esterne: tutte le librerie sono servite dal server
- Solo lettura sul database

## Requisiti

- PHP 8.0 o superiore, con estensioni `mysqli`, `ldap`, `mbstring`
- Composer per le dipendenze PHP (PhpSpreadsheet, Adldap2)
- Accesso di rete al database MySQL e al Domain Controller

## Installazione

1. Installa le dipendenze con Composer:
```bash
composer install
```

2. Configura `config.php`:
   - database: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (utente con soli permessi di lettura)
   - Active Directory: `AD_HOST`, `AD_BASE_DN`, `AD_ACCOUNT_SUFFIX`, `AD_ADMIN_USERNAME`, `AD_ADMIN_PASSWORD`

3. Assicurati che il server web abbia accesso al database MySQL e al Domain Controller.

## Struttura

| File | Ruolo |
|---|---|
| `login.php` | Pagina di accesso (Active Directory) |
| `index.php` | Pagina principale: periodo, filtro consenso, tabella, download |
| `data.php` | Endpoint JSON che fornisce la tabella pagina per pagina |
| `export.php` | Genera il file Excel con lo stesso filtro della tabella |
| `config.php` | Credenziali e funzioni di accesso al database |
| `src/Auth.php` | Autenticazione Active Directory e sessione |
| `src/output.php` | Invia ogni risposta PHP con `Content-Length` (vedi "Risoluzione problemi") |
| `app.css` | Unico foglio di stile (colori, spaziature, tema scuro) |
| `assets/vendor/` | Librerie front-end: jQuery 3.7.0, DataTables 1.13.7 (con traduzione italiana), flatpickr 4.6.13 |

## Aggiornamento su Altro Server

### Primo Setup (Clone del Repository)

```bash
# Clona il repository
git clone https://github.com/voidman73/miccard_view.git
cd miccard_view

# Installa le dipendenze
composer install

# Configura config.php con le credenziali del nuovo server
```

### Aggiornamento (Pull)

```bash
cd /percorso/del/progetto/miccard_view
git pull origin main
composer install
```

## Utilizzo

1. Apri `index.php` nel browser e accedi con nome utente e password del PC aziendale
2. In "Periodo di registrazione" scegli le date "Registrati dal" e "al (incluso)" (si possono anche digitare, formato gg/mm/aaaa), poi premi "Cerca"
3. In "Consenso" scegli "Solo newsletter" o "Newsletter + iniziative culturali": accanto a ogni opzione c'è il numero di email
4. Usa il campo di ricerca della tabella per filtrare l'elenco
5. Premi "Scarica N email (.xls)": il file contiene le stesse email dell'elenco filtrato
6. "Azzera" riporta il periodo alla data odierna

## File Generati

- `email_newsletter_{dal}_{al}.xls` - Solo newsletter
- `email_newsletter_cultura_{dal}_{al}.xls` - Newsletter + iniziative culturali
- Il suffisso `_filtrato` viene aggiunto quando è attiva una ricerca

## Sicurezza

- Login obbligatorio per tutte le pagine e per gli endpoint `data.php` ed `export.php`
- L'export usa le date salvate in sessione dall'ultima ricerca, non valori inviati dal browser
- Tutte le query utilizzano prepared statements per prevenire SQL injection
- La connessione al database è in modalità sola lettura
- Gli errori del database vengono registrati nel log del server; all'utente compare solo un messaggio generico

## Risoluzione problemi

**`net::ERR_INCOMPLETE_CHUNKED_ENCODING` nel browser**
La rete tra i client (192.168.1.x) e il server tronca le risposte HTTP inviate "a pezzi" (chunked). Per questo:
- le librerie JavaScript/CSS sono in `assets/vendor/` e non vengono caricate da CDN;
- `src/output.php` bufferizza ogni risposta PHP e la invia con `Content-Length`; deve restare il primo `require` di ogni pagina PHP;
- `export.php` genera il file Excel su un file temporaneo e lo invia con `Content-Length`.

Se l'errore ricompare, controllare nel browser (F12 → Rete) quale risorsa è in rosso.

**"Troppi risultati per un file Excel"**
Il formato .xls supporta al massimo 65.535 righe: restringere il periodo o usare la ricerca.

## Note

⚠️ **IMPORTANTE**: `config.php` contiene le credenziali del database e dell'account di servizio Active Directory. Non committarlo con le credenziali reali in repository pubblici.
