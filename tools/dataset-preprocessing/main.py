import json
import logging
from pathlib import Path

import cli

def prelim_check(file, verbose=False):
    logging.info(f"Checking file: {file}")

    if not file.exists():
        logging.error(f"The path for dataset '{file}' does not exist.")

    if not file.is_file():
        logging.error(f"The dataset '{file}' is not a file")

def parse(file):
    with open(file, "r") as data:
        try:
            return json.load(data)
        except Exception as e:
            logging.error(f"Failed to parse '{file}' as json:\n\t{e}")

def main():
    args = cli.parser_init()

    file = args.file
    adapter = args.adapter
    verbose = args.verbose

    logging.basicConfig(level=int(verbose), format="[%(levelname)s] %(message)s")

    prelim_check(file, verbose=verbose)
    dataset = parse(file)




if __name__ == "__main__":
    main()
