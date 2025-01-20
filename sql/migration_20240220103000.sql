-- KAN-81
-- ======
-- Voir <https://mdp-data.atlassian.net/browse/KAN-81>
-- passage des champs `purpose` de 255 à 510 caractères
-- pour les tables treatment et treatment_std
alter table treatment modify column main_purpose varchar(510);
alter table treatment modify column purpose_1 varchar(510);
alter table treatment modify column purpose_2 varchar(510);
alter table treatment modify column purpose_3 varchar(510);
alter table treatment modify column purpose_4 varchar(510);
alter table treatment modify column purpose_5 varchar(510);
alter table treatment modify column others_purpose varchar(510);

alter table treatment_std modify column main_purpose varchar(510);
alter table treatment_std modify column purpose_1 varchar(510);
alter table treatment_std modify column purpose_2 varchar(510);
alter table treatment_std modify column purpose_3 varchar(510);
alter table treatment_std modify column purpose_4 varchar(510);
alter table treatment_std modify column purpose_5 varchar(510);
alter table treatment_std modify column others_purpose varchar(510);
