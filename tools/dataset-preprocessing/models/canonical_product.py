from pydantic import BaseModel, Field

class CanonicalProduct(BaseModel):
    external_id:    str
    source_dataset: str

    name:           str
    slug:           str
    category:       str
    price:          str
    vendor:         str
    stock:          int = Field(ge=0)

    features:       list[str] = Field(default_factory=list)
    description:    str | None = None
    images:         list[str] = Field(default_factory=list)
    videos:         list[str] = Field(default_factory=list)
    details:        dict[str, object] = Field(default_factory=dict)
    tags:           list[str] = Field(default_factory=list)

    average_rating: float | None = None
    total_reviews:  int | None = None
    provenance:     str | None = None
