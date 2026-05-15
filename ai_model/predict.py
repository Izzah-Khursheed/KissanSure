import torch
import torch.nn as nn
import torch.nn.functional as F
from torchvision import transforms
from torchvision.models import mobilenet_v2
from PIL import Image

device = "cuda" if torch.cuda.is_available() else "cpu"

# ───────────────────────────────────────────────────────────────
# CLASSIFICATION MODELS  (MobileNetV2 binary — damaged / healthy)
# One model per supported crop. Add new crops below.
# ───────────────────────────────────────────────────────────────

_clf_classes = ['damaged', 'healthy']

_clf_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize(mean=[0.485, 0.456, 0.406],
                         std =[0.229, 0.224, 0.225]),
])


def _load_classifier(model_path):
    model = mobilenet_v2()
    model.classifier[1] = nn.Linear(model.last_channel, 2)
    model.load_state_dict(torch.load(model_path, map_location=device))
    model.to(device)
    model.eval()
    return model


# -- load models at startup --
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


# ───────────────────────────────────────────────────────────────
# SEGMENTATION MODEL — DISABLED
# Uncomment everything below (and the /segment endpoint in main.py)
# to re-enable segmentation.
# ───────────────────────────────────────────────────────────────

# from torchvision.models import resnet34
#
# _seg_transform = transforms.Compose([
#     transforms.Resize((256, 256)),
#     transforms.ToTensor(),
#     transforms.Normalize(mean=[0.485, 0.456, 0.406],
#                          std =[0.229, 0.224, 0.225]),
# ])
#
#
# class _DecoderBlock(nn.Module):
#     def __init__(self, in_ch, out_ch):
#         super().__init__()
#         self.conv1 = nn.Sequential(
#             nn.Conv2d(in_ch, out_ch, 3, padding=1, bias=False),
#             nn.BatchNorm2d(out_ch),
#             nn.ReLU(inplace=True),
#         )
#         self.conv2 = nn.Sequential(
#             nn.Conv2d(out_ch, out_ch, 3, padding=1, bias=False),
#             nn.BatchNorm2d(out_ch),
#             nn.ReLU(inplace=True),
#         )
#
#     def forward(self, x, skip=None):
#         x = F.interpolate(x, scale_factor=2, mode="bilinear", align_corners=False)
#         if skip is not None:
#             x = torch.cat([x, skip], dim=1)
#         return self.conv2(self.conv1(x))
#
#
# class _RiceSegModel(nn.Module):
#     def __init__(self):
#         super().__init__()
#         backbone     = resnet34(weights=None)
#         self.encoder = backbone
#         self.decoder = nn.ModuleDict({
#             "blocks": nn.ModuleList([
#                 _DecoderBlock(768, 256),
#                 _DecoderBlock(384, 128),
#                 _DecoderBlock(192,  64),
#                 _DecoderBlock(128,  32),
#                 _DecoderBlock( 32,  16),
#             ])
#         })
#         self.segmentation_head = nn.Sequential(
#             nn.Conv2d(16, 1, 3, padding=1)
#         )
#
#     def forward(self, x):
#         x_stem = F.relu(self.encoder.bn1(self.encoder.conv1(x)))
#         x_pool = self.encoder.maxpool(x_stem)
#         x1     = self.encoder.layer1(x_pool)
#         x2     = self.encoder.layer2(x1)
#         x3     = self.encoder.layer3(x2)
#         x4     = self.encoder.layer4(x3)
#         d = self.decoder["blocks"][0](x4, x3)
#         d = self.decoder["blocks"][1](d,  x2)
#         d = self.decoder["blocks"][2](d,  x1)
#         d = self.decoder["blocks"][3](d,  x_stem)
#         d = self.decoder["blocks"][4](d)
#         return torch.sigmoid(self.segmentation_head(d))
#
#
# def _load_seg_model(model_path):
#     model = _RiceSegModel()
#     state_dict = torch.load(model_path, map_location=device)
#     model.load_state_dict(state_dict, strict=False)
#     model.to(device)
#     model.eval()
#     return model
#
# rice_seg_model = _load_seg_model("rice_segmentation_model.pth")
#
#
# def segment(image_path, crop_type):
#     if crop_type.lower() != "rice":
#         return {"error": "Segmentation not available for crop: " + crop_type}
#     image  = Image.open(image_path).convert("RGB")
#     tensor = _seg_transform(image).unsqueeze(0).to(device)
#     with torch.no_grad():
#         output = rice_seg_model(tensor)
#     damage_percent = round(output.squeeze().mean().item() * 100, 2)
#     return {"damage_percent": damage_percent}
