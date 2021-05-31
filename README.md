# Cryptography's Wiki

## Prerequisiti

- [PHP 7.x](https://www.php.net/downloads)
- [Composer](https://getcomposer.org/download/)
- [Symfony](https://symfony.com/download)
- [MySQL](https://dev.mysql.com/downloads/)

## Set up

- Dopo aver scaricato il progetto si devono installare le dipendenze richieste:

  ```sh
  composer update
  ```

- Imposta  l'URL del database modificando il `DATABASE_URL` nel file `.env`:

  Si devono modificare `user`, `password`, `host` e `port` con le tue credenziali MySQL.

- Genera il database e le tabelle usando [Doctrine](https://www.doctrine-project.org/):

  ```sh
  php bin/console doctrine:database:create
  php bin/console doctrine:schema:create
  ```

- Adesso puoi far partire la web app con il comando:

  ```sh
  symfony serve
  ```
