import argparse
from pathlib import Path


def parser_init():
    """Creates the argument parser and returns the parsed arguments & subcommands."""
    parser = argparse.ArgumentParser(
        description="Larp Shop dataset preprocessor cli interface"
    )

    parser.add_argument("-v", "--verbose", action="store_true", help="Enable verbose output.")

    subparsers = parser.add_subparsers(
        dest="command", required=False, help="Available subcommands"
    )

    inspect = subparsers.add_parser(
        name="inspect",
        help="Inspect the source structure and available columns",
        description="Inspect the source structure and available columns",
    )
    inspect.add_argument("file", type=str, help="Raw dataset file, either .csv or .json")

    normalize = subparsers.add_parser(
        name="normalize", help="Normalize records without changing data"
    )
    normalize.add_argument("-a", "--adapter", required=True, type=Path, help="The path to the raw dataset file.")
    normalize.add_argument("-o", "--output", required=True, type=Path, help="The path of the output file")

    enrich = subparsers.add_parser(
        name="enrich", help="Enrich records and match images"
    )
    enrich.add_argument("file", type=Path)

    validate = subparsers.add_parser(
        name="validate", help="Validate and display a report"
    )
    validate.add_argument("file", type=Path)

    merge = subparsers.add_parser(
        name="merge", help="Merge into existing output file incrementally"
    )
    merge.add_argument("file", type=Path)
    merge.add_argument("--dry-run", help="Preview the proposed merge")

    parser.add_argument("-f", "--file", type=Path, required=True, help="The path to the raw dataset file.")
    parser.add_argument("-a", "--adapter", type=Path, required=True, help="The adapter used on the dataset.")
    parser.add_argument("-m", "--model", type=str, required=True, help="The model")
    parser.add_argument("-p", "--profile", type=str, required=False, default="development_seed", help="Define what must be present for a particular use case")

    return parser.parse_args()
