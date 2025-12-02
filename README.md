# Query Database Store MiCCard - Email Export System

Interfaccia web portatile in PHP 8.x per interrogare un database MySQL e esportare email clienti.

## Caratteristiche

- ✅ Interfaccia moderna e responsive
- ✅ Selezione date con calendario
- ✅ Due query SQL predefinite
- ✅ Export risultati in formato Excel
- ✅ Solo lettura sul database (sicurezza)
- ✅ Tema azzurro e bianco personalizzato
- ✅ Paginazione (50 record per pagina)
- ✅ Tabelle affiancate per confronto rapido

## Requisiti

- PHP 8.0 o superiore
- Estensione MySQLi abilitata
- Composer per gestione dipendenze

## Installazione

1. Installa le dipendenze con Composer:
```bash
composer install
```

2. Verifica che il file `config.php` contenga le credenziali corrette del database.

3. Assicurati che il server web abbia accesso al database MySQL.

## Aggiornamento su Altro Server

### Primo Setup (Clone del Repository)

Se è la prima volta che installi su un nuovo server:

```bash
# Clona il repository
git clone https://github.com/voidman73/miccard_view.git

# Entra nella directory
cd miccard_view

# Installa le dipendenze
composer install

# Configura il file config.php con le credenziali del database del nuovo server
# (modifica DB_HOST, DB_NAME, DB_USER, DB_PASS se necessario)
```

### Aggiornamento File Esistenti (Pull)

Se il repository esiste già sul server e vuoi aggiornarlo con le ultime modifiche:

```bash
# Entra nella directory del progetto
cd /percorso/del/progetto/miccard_view

# Scarica le ultime modifiche da GitHub
git pull origin main

# Aggiorna le dipendenze (se ci sono nuove librerie)
composer install
```

### Comandi Rapidi

```bash
# Verifica lo stato del repository
git status

# Vedi le ultime modifiche
git log --oneline -5

# Aggiorna tutto in un comando
git pull && composer install
```

## Utilizzo

1. Apri `index.php` nel browser
2. Seleziona le date di inizio e fine (default: data odierna)
3. Clicca su "Esegui Query"
4. Visualizza i risultati nelle due tabelle affiancate
5. Naviga tra le pagine se ci sono più di 50 risultati
6. Esporta i dati in Excel cliccando sul pulsante "Esporta Excel" di ogni tabella

## File Generati

I file Excel vengono generati con i seguenti nomi:
- `ExportEmail_normal_{From-To}.xls` - Query 1
- `ExportEmail_with_culture_{From-To}.xls` - Query 2

## Sicurezza

- Tutte le query utilizzano prepared statements per prevenire SQL injection
- La connessione è configurata in modalità sola lettura
- Validazione input lato server

## Note

⚠️ **IMPORTANTE**: Il file `config.php` contiene credenziali del database. Assicurati di non committare questo file in repository pubblici o di renderlo privato.
