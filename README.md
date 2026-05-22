# Päeva Hääletus

<img width="592" height="565" alt="image" src="https://github.com/user-attachments/assets/229460f0-2f88-46ec-9b10-9c77051b8395" />
<img width="546" height="525" alt="image" src="https://github.com/user-attachments/assets/902f0ff3-d95c-4b54-a3b5-7c05cabbec99" />


Minimalistlik dark-mode veebirakendus, mis küsib kasutajalt ühe lihtsa küsimuse:

> **Kas täna on ok päev?**

Rakendus on kirjutatud ühe failina (`index.php`) ning kasutab:
- PHP 8+
- MySQL / MariaDB
- Vanilla JavaScript
- HTML5 + CSS3

Projekt sisaldab sessioonipõhist hääletamist, anonüümset tagasisidet ning modernset animatsioonidega kasutajaliidest.

---

# Preview

## Positiivne vastus

![Positive State](./preview-up.png)

## Negatiivne vastus

![Negative State](./preview-down.png)

---

# Funktsionaalsus

## Sessioonipõhine hääletamine

- Kasutaja saab hääletada ühe korra sessiooni jooksul
- Sessiooni ID genereeritakse `random_bytes()` abil
- Hääletuse olek taastatakse automaatselt lehe uuesti laadimisel

---

## Positiivne / negatiivne olek

### 👍 Jah
- Aktiivne roheline kaart
- Positiivne tagasiside
- Ripple animatsioon

### 👎 Ei
- Avab modal-akna
- Võimaldab jätta anonüümset tagasisidet
- Kuvab toetava sõnumi pärast saatmist

---

## Modal süsteem

Negatiivse vastuse korral avaneb:
- blur-taustaga overlay
- animeeritud modal
- textarea tagasiside jaoks
- ESC sulgemine
- click-outside sulgemine

---

## UI ja animatsioonid

Projekt sisaldab:
- Dark mode disain
- SVG noise background
- Hover animatsioonid
- Ripple effect
- Smooth transitions
- Responsive layout
- Custom typography (`Syne` + `DM Sans`)

---

# Turvalisus

Rakendus kasutab:
- PDO prepared statements
- JSON input validation
- Feedback pikkuse limiit (`1000`)
- Sessioonipõhist identifikaatorit
- `utf8mb4` charseti

---

# Andmebaas

Rakendus loob tabeli automaatselt käivitamisel.

## SQL struktuur

```sql
CREATE TABLE IF NOT EXISTS paev_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    vote ENUM('up','down') NOT NULL,
    feedback TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

# Projekti struktuur

```text
project/
│
├── index.php
├── .env
├── preview-up.png
└── preview-down.png
```

---

# .env seadistus

Loo `.env` fail:

```env
DB_HOST=localhost
DB_NAME=database_name
DB_USER=username
DB_PASS=password
```

---

# API

Rakendus töötab väikese sisseehitatud API-na.

---

## GET `?action=get`

Tagastab kasutaja olemasoleva hääletuse.

### Vastus

```json
{
  "success": true,
  "vote": "up"
}
```

või

```json
{
  "success": true,
  "vote": null
}
```

---

## POST `?action=vote`

Salvestab või uuendab hääletust.

### Request

```json
{
  "vote": "down",
  "feedback": "Täna oli raske päev."
}
```

### Response

```json
{
  "success": true,
  "vote": "down"
}
```

---

# Kasutatud tehnoloogiad

| Tehnoloogia | Kirjeldus |
|---|---|
| PHP 8+ | Backend ja API |
| MySQL | Andmete salvestus |
| PDO | Turvalised päringud |
| Vanilla JS | Frontend loogika |
| CSS3 | UI ja animatsioonid |

---

# Käivitamine

## 1. Clone

```bash
git clone https://github.com/sinu-user/paeva-haaletus.git
```

## 2. Lisa `.env`

```env
DB_HOST=localhost
DB_NAME=database
DB_USER=root
DB_PASS=password
```

## 3. Ava browseris

```text
http://localhost/index.php
```

---

# Märkused

- `.env` fail ei tohiks olla public accessiga
- Soovitatav lisada `.gitignore`
- Sama sessiooni hääletus kirjutatakse üle (`ON DUPLICATE KEY UPDATE`)
- Projekt töötab ühe failina ilma frameworkideta
