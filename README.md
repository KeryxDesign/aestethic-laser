# GentleTest — landing prenotazione

Sito di prenotazione del **GentleTest** (trattamento di prova gratuito di epilazione laser) del centro Gentle Beam, Palermo.

- **Stack:** Astro 6 statico
- **Dominio:** https://gentletest.it (URL pubblico della landing: `/test`)
- **Hosting:** Hostinger (deploy automatico via FTP)

## Sviluppo

```bash
npm install
npm run dev      # http://localhost:4340/test
npm run build    # genera dist/
```

## Deploy

Push su `main` → GitHub Actions builda Astro e carica `dist/` via FTP su Hostinger.

Secrets richiesti nel repo (Settings → Secrets and variables → Actions):

| Secret | Valore |
|--------|--------|
| `FTP_SERVER` | host FTP Hostinger (es. `ftp.gentletest.it` o IP) |
| `FTP_USERNAME` | utente FTP |
| `FTP_PASSWORD` | password FTP |
| `FTP_REMOTE_DIR` | cartella docroot (es. `.` oppure `/public_html`) |

> Finché i secret non sono impostati, il deploy non pubblica nulla.

## Pagine

- `/test` — landing principale (la home `/` reindirizza qui)
- `/grazie` — ringraziamento post-form
- `/regolamento-garanzia` — regolamento garanzia (DA COMPLETARE prima del lancio)
- `public/lead.php` — handler form (richiede hosting con PHP)

## TODO prima del lancio (DA CONFERMARE)

- [ ] Regolamento garanzia reale (Greg + LEX) in `/regolamento-garanzia`
- [ ] Email del centro in `public/lead.php` (`$to`)
- [ ] P.IVA, telefono, email nel footer di `/test`
- [ ] Nome + foto reale della professionista
- [ ] Una recensione vera (nome + città)
- [ ] Verificare che l'hosting Hostinger supporti PHP `mail()` (altrimenti Formspree/SMTP)
