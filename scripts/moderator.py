import sys
import json
import re

# Categorized forbidden words
CATEGORIES = {
    "Insulte": ["idiot", "débile", "con", "salaud", "merde", "pute", "enculé", "foufou", "connard", "salope"],
    "Toxicity": ["tuer", "mort", "haine", "déteste", "raciste", "nazi", "violence", "suicide", "sang", "menace"],
    "Spam": ["viagra", "casino", "gagner argent", "cliquez ici", "sexy", "gratuit", "vendre", "achat", "promo"]
}

def analyze_text(text):
    text_lower = text.lower().strip()
    
    if not text_lower:
        return "APPROVED", "Empty content", ""

    # Check for shouting (All caps)
    if len(text) > 5 and text.isupper():
        return "BLOCKED", "Toxicity (Shouting)", text
    
    # Check categories with more robust word boundary matching and character repetition
    for category, words in CATEGORIES.items():
        for word in words:
            # Create a regex pattern that handles repeated characters (e.g., "morrt" matching "mort")
            # We add \b for word boundaries if possible, but keep it flexible for toxicity
            pattern_str = r"".join([re.escape(c) + r"+" for c in word])
            pattern = re.compile(pattern_str, re.IGNORECASE)
            
            if pattern.search(text_lower):
                return "BLOCKED", f"Catégorie détectée: {category} (Mot: {word} - Pattern match)", text

    # Clean text (Sanitization)
    cleaned_text = text
    for words in CATEGORIES.values():
        for word in words:
            pattern = re.compile(re.escape(word), re.IGNORECASE)
            cleaned_text = pattern.sub("*" * len(word), cleaned_text)

    return "APPROVED", "Safe content", cleaned_text

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No text provided"}))
        sys.exit(1)

    input_text = sys.argv[1]
    status, reason, cleaned = analyze_text(input_text)
    
    result = {
        "status": status,
        "reason": reason,
        "cleanedText": cleaned
    }
    
    print(json.dumps(result))
