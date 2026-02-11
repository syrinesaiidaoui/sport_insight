-- SQL Migration: Add description column to contrat_sponsor table
-- Execute this in phpMyAdmin or your MySQL client

ALTER TABLE `contrat_sponsor` 
ADD COLUMN `description` LONGTEXT NULL AFTER `equipe_id`;
