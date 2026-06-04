# ML prediction module — loads the pre-trained MobileNetV2 models and classifies
# crop images as "damaged" or "healthy". Called by main.py for every uploaded image.

import torch
import torch.nn as nn
import torch.nn.functional as F
from torchvision import transforms
from torchvision.models import mobilenet_v2
from PIL import Image

# Use GPU if available (Railway/cloud), otherwise fall back to CPU
device = "cuda" if torch.cuda.is_available() else "cpu"

# ───────────────────────────────────────────────────────────────
# CLASSIFICATION MODELS  (MobileNetV2 binary — damaged / healthy)
# One model per supported crop. Add new crops below.
# ───────────────────────────────────────────────────────────────

# Binary class labels — index 0 = damaged, index 1 = healthy
_clf_classes = ['damaged', 'healthy']

# Standard ImageNet preprocessing applied to every input image before inference
_clf_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize(mean=[0.485, 0.456, 0.406],
                         std =[0.229, 0.224, 0.225]),
])


def _load_classifier(model_path):
    # Replace the final FC layer with a 2-class output head, then load saved weights
    model = mobilenet_v2()
    model.classifier[1] = nn.Linear(model.last_channel, 2)
    model.load_state_dict(torch.load(model_path, map_location=device))
    model.to(device)
    model.eval()
    return model


# -- load models at startup (once when the server starts, not per request) --
rice_model  = _load_classifier("rice_classification_model.pth")
wheat_model = _load_classifier("wheat_classification_model.pth")

# Maps lowercase crop name → loaded model
_CROP_MODELS = {
    "rice":  rice_model,
    "wheat": wheat_model,
}


def predict(image_path, crop_type):
    """Run binary damage classification on a single image.

    Returns:
        {"class": "damaged"|"healthy", "confidence": float}
        {"error": str}  if crop not supported
    """
    key = crop_type.lower()
    model = _CROP_MODELS.get(key)

    if model is None:
        supported = ", ".join(_CROP_MODELS.keys())
        return {"error": f"Model not available for crop '{crop_type}'. Supported: {supported}"}

    image  = Image.open(image_path).convert("RGB")
    tensor = _clf_transform(image).unsqueeze(0).to(device)

    with torch.no_grad():
        outputs = model(tensor)
        probs   = F.softmax(outputs, dim=1)
        conf, pred = torch.max(probs, 1)

    return {
        "class":      _clf_classes[pred.item()],
        "confidence": round(conf.item() * 100, 2),
    }
