<?php

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service for handling all server-side validation
 * This service centralizes all validation logic
 */
class ValidationService
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * Validate an entity and return array of error messages
     * 
     * @param object $entity The entity to validate
     * @return array Array of error messages keyed by field
     */
    public function validate(object $entity): array
    {
        $violations = $this->validator->validate($entity);
        $errors = [];

        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            if (!isset($errors[$field])) {
                $errors[$field] = [];
            }
            $errors[$field][] = $violation->getMessage();
        }

        return $errors;
    }

    /**
     * Check if validation has errors
     * 
     * @param object $entity
     * @return bool
     */
    public function hasErrors(object $entity): bool
    {
        return count($this->validate($entity)) > 0;
    }

    /**
     * Get flattened error messages
     * 
     * @param object $entity
     * @return array
     */
    public function getFlattenedErrors(object $entity): array
    {
        $errors = $this->validate($entity);
        $flatErrors = [];

        foreach ($errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $flatErrors[] = $error;
            }
        }

        return $flatErrors;
    }

    /**
     * Custom validation for Product
     * 
     * @param array $data
     * @return array
     */
    public function validateProductData(array $data): array
    {
        $errors = [];

        // Name validation
        if (empty($data['name'] ?? null)) {
            $errors['name'][] = 'Le nom du produit est obligatoire';
        } elseif (strlen($data['name']) < 3) {
            $errors['name'][] = 'Le nom doit contenir au moins 3 caractères';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'][] = 'Le nom ne peut pas dépasser 255 caractères';
        }

        // Price validation
        if (empty($data['price'] ?? null)) {
            $errors['price'][] = 'Le prix est obligatoire';
        } elseif (!is_numeric($data['price'])) {
            $errors['price'][] = 'Le prix doit être un nombre';
        } elseif ((float)$data['price'] < 0) {
            $errors['price'][] = 'Le prix doit être positif';
        } elseif ((float)$data['price'] > 999999.99) {
            $errors['price'][] = 'Le prix est trop élevé';
        }

        // Stock validation
        if ($data['stock'] === null || $data['stock'] === '') {
            $errors['stock'][] = 'Le stock est obligatoire';
        } elseif (!is_numeric($data['stock'])) {
            $errors['stock'][] = 'Le stock doit être un nombre entier';
        } elseif ((int)$data['stock'] < 0) {
            $errors['stock'][] = 'Le stock ne peut pas être négatif';
        } elseif ((int)$data['stock'] > 999999) {
            $errors['stock'][] = 'Le stock est trop élevé';
        }

        // Category validation
        if (!empty($data['category'] ?? null) && strlen($data['category']) > 255) {
            $errors['category'][] = 'La catégorie ne peut pas dépasser 255 caractères';
        }

        // Size validation
        if (!empty($data['size'] ?? null) && strlen($data['size']) > 10) {
            $errors['size'][] = 'La taille est invalide';
        }

        // Brand validation
        if (!empty($data['brand'] ?? null) && strlen($data['brand']) > 30) {
            $errors['brand'][] = 'La marque est invalide';
        }

        return $errors;
    }

    /**
     * Custom validation for Order
     * 
     * @param array $data
     * @return array
     */
    public function validateOrderData(array $data): array
    {
        $errors = [];

        // Quantity validation
        if ($data['quantity'] === null || $data['quantity'] === '') {
            $errors['quantity'][] = 'La quantité est obligatoire';
        } elseif (!is_numeric($data['quantity'])) {
            $errors['quantity'][] = 'La quantité doit être un nombre entier';
        } elseif ((int)$data['quantity'] <= 0) {
            $errors['quantity'][] = 'La quantité doit être au moins 1';
        } elseif ((int)$data['quantity'] > 999999) {
            $errors['quantity'][] = 'La quantité est trop élevée';
        }

        // Order Date validation
        if (empty($data['orderDate'] ?? null)) {
            $errors['orderDate'][] = 'La date de commande est obligatoire';
        } else {
            try {
                $date = $data['orderDate'] instanceof \DateTime ? $data['orderDate'] : new \DateTime($data['orderDate']);
                if ($date > new \DateTime()) {
                    $errors['orderDate'][] = 'La date ne peut pas être dans le futur';
                }
            } catch (\Exception $e) {
                $errors['orderDate'][] = 'La date est invalide';
            }
        }

        // Status validation
        $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered'];
        if (empty($data['status'] ?? null)) {
            $errors['status'][] = 'Le statut est obligatoire';
        } elseif (!in_array($data['status'], $validStatuses)) {
            $errors['status'][] = 'Le statut est invalide';
        }

        // Product validation
        if (empty($data['product'] ?? null)) {
            $errors['product'][] = 'Un produit doit être sélectionné';
        }

        // Trainer validation
        if (empty($data['entraineur'] ?? null)) {
            $errors['entraineur'][] = 'Un entraîneur doit être sélectionné';
        }

        return $errors;
    }
}
