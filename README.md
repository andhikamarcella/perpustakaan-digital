# perpustakaan-digital

## Environment Variables

The application reads database credentials from the environment. Set the
following variables before running any PHP scripts:

- `DB_HOST` – database host
- `DB_USER` – database username
- `DB_PASS` – database password
- `DB_NAME` – database name

For local development you can copy `local_env.sh.example` to `local_env.sh`,
fill in the correct values, and source it:

```sh
cp local_env.sh.example local_env.sh
# edit local_env.sh with your credentials
source local_env.sh
```

The scripts will then pick up the credentials via `getenv()` calls.
