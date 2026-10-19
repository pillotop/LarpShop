from typing import Any
from pydantic import BaseModel, ConfigDict


class RawRecord(BaseModel):
    model_config = ConfigDict(extra="allow")

    source_dataset: str
    source_id: str | None = None
    data: dict[str, Any]
