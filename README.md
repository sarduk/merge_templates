# merge_templates
A PHP CLI script for merging and copying files between directories while preserving structure and managing content integration.
newline prova

### Usage:
php merge_templates.php [OPTIONS] path_source_dir/ path_target_dir/

[OPTIONS]

      --force               Overwrite files in path_target_dir/ without prompting

      --help, -h            Display this help message

      --version, -v         Display the version of the script


### Come funziona lo script:


1. **Parsing delle opzioni:**
   - `--force`: Se presente, sovrascrive i file in `path_target_dir/` senza chiedere conferma.
   - `--help` o `-h`: Mostra un messaggio di aiuto e termina lo script.
   - `--version` o `-v`: Mostra la versione dello script e termina.

2. **Processo di merge:**
   - Lo script esamina i file in `path_source_dir/` e li copia in `path_target_dir/`, mantenendo la stessa struttura di directory.
   - Se un file in `path_source_dir/` corrisponde al formato `+numero_nomefile`, il contenuto di quel file viene aggiunto in un punto specifico del file target, corrispondente al placeholder presente nella prima riga del file di tipo `merge_add_content`.
   - Se il file target non esiste, viene lanciata un'eccezione.

3. **Gestione degli errori:**
   - Lo script lancia eccezioni se si verificano problemi come file già esistenti (senza `--force`) o file target mancanti per le operazioni di merge.

### Come utilizzare lo script:

- Per unire directory senza sovrascrivere file:
  ```bash
  php merge_templates.php path_source_dir/ path_target_dir/
  ```

- Per unire directory e sovrascrivere file esistenti:
  ```bash
  php merge_templates.php --force path_source_dir/ path_target_dir/
  ```

- Per mostrare la guida:
  ```bash
  php merge_templates.php --help
  ```

- Per mostrare la versione:
  ```bash
  php merge_templates.php --version
  ```

Questo script offre un approccio completo per gestire la fusione e copia di file tra directory, con attenzione particolare ai casi di aggiunta di contenuti specifici.


