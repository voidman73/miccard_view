# Istruzioni per caricare su GitHub

## Dopo aver creato il repository su GitHub, esegui questi comandi:

```bash
# Aggiungi il remote (sostituisci TUO_USERNAME con il tuo username GitHub)
git remote add origin https://github.com/voidman73/miccard_view.git

# Rinomina il branch principale in 'main' (se GitHub usa main invece di master)
git branch -M main

# Carica il codice su GitHub
git push -u origin main
```

## Se GitHub usa 'master' invece di 'main':

```bash
git remote add origin https://github.com/TUO_USERNAME/miccard_view.git
git push -u origin master
```

## Note importanti:

⚠️ **SICUREZZA**: Il file `config.php` contiene credenziali del database. 
Considera di:
- Rendere il repository privato, OPPURE
- Usare variabili d'ambiente per le credenziali, OPPURE
- Aggiungere `config.php` al `.gitignore` e creare un `config.example.php` con valori di esempio

## Per aggiornare il repository in futuro:

```bash
git add .
git commit -m "Descrizione delle modifiche fatte"
git push
```

