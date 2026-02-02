# Test des Endpoints de Détails d'Analyse

## 1. Ajouter les détails d'une analyse

**Endpoint:** `POST /api/analyse/{analyseId}/details`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "details": [
    {
      "nom": "Leucocyte",
      "resultat": "4500",
      "intervalle_reference": "4000-11000 /mm³"
    },
    {
      "nom": "Neutrophile",
      "resultat": "2500",
      "intervalle_reference": "2000-7500 /mm³"
    },
    {
      "nom": "Lymphocyte",
      "resultat": "1500",
      "intervalle_reference": "1000-4000 /mm³"
    },
    {
      "nom": "Monocyte",
      "resultat": "500",
      "intervalle_reference": "200-800 /mm³"
    }
  ]
}
```

**Exemple pour l'analyse "Numération leucocytaire":**
```bash
curl -X POST http://127.0.0.1:8000/api/analyse/1/details \
  -H "Authorization: Bearer {votre_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "details": [
      {
        "nom": "Leucocyte",
        "resultat": "4500",
        "intervalle_reference": "4000-11000 /mm³"
      },
      {
        "nom": "Neutrophile", 
        "resultat": "2500",
        "intervalle_reference": "2000-7500 /mm³"
      },
      {
        "nom": "Lymphocyte",
        "resultat": "1500", 
        "intervalle_reference": "1000-4000 /mm³"
      },
      {
        "nom": "Monocyte",
        "resultat": "500",
        "intervalle_reference": "200-800 /mm³"
      }
    ]
  }'
```

## 2. Récupérer les détails d'une analyse

**Endpoint:** `GET /api/analyse/{analyseId}/details`

**Headers:**
```
Authorization: Bearer {token}
```

**Exemple:**
```bash
curl -X GET http://127.0.0.1:8000/api/analyse/1/details \
  -H "Authorization: Bearer {votre_token}"
```

**Réponse attendue:**
```json
{
  "analyse_id": 1,
  "details": [
    {
      "nom": "Leucocyte",
      "resultat": "4500",
      "intervalle_reference": "4000-11000 /mm³"
    },
    {
      "nom": "Neutrophile",
      "resultat": "2500", 
      "intervalle_reference": "2000-7500 /mm³"
    },
    {
      "nom": "Lymphocyte",
      "resultat": "1500",
      "intervalle_reference": "1000-4000 /mm³"
    },
    {
      "nom": "Monocyte",
      "resultat": "500",
      "intervalle_reference": "200-800 /mm³"
    }
  ]
}
```

## 3. Mise à jour des détails

Si des détails existent déjà pour une analyse, l'endpoint `POST` les mettra à jour automatiquement.

## 4. Structure des données

Chaque détail d'analyse contient :
- **nom**: Le nom du paramètre (ex: "Leucocyte", "Hémoglobine")
- **resultat**: La valeur mesurée (ex: "4500", "14.2")
- **intervalle_reference**: Les valeurs normales (ex: "4000-11000 /mm³", "12-16 g/dL")

## 5. Exemples d'autres analyses

**Hémoglobine:**
```json
{
  "details": [
    {
      "nom": "Hémoglobine",
      "resultat": "14.2",
      "intervalle_reference": "12-16 g/dL"
    },
    {
      "nom": "Hématocrite", 
      "resultat": "42",
      "intervalle_reference": "36-46 %"
    },
    {
      "nom": "Hématies",
      "resultat": "5.1",
      "intervalle_reference": "4.5-5.5 M/μL"
    }
  ]
}
```

**Plaquettes:**
```json
{
  "details": [
    {
      "nom": "Plaquettes",
      "resultat": "250000",
      "intervalle_reference": "150000-400000 /mm³"
    }
  ]
}
``` 