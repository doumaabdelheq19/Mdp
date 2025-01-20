-- KAN-226
-- =======
-- suppression de la contrainte FK pour treatment
alter table treatment drop constraint treatment_user_id_fk;

-- rétablissement de la contrainte avec correctif dans treatment
alter table treatment 
add constraint treatment_user_id_fk 
foreign key(user_id) 
    references user(id)
    on delete set null;



-- suppression de la contrainte FK pour treatment_std
alter table treatment_std drop constraint treatment_std_user_id_fk;

-- rétablissement de la contrainte avec correctif dans treatment_std
alter table treatment_std 
add constraint treatment_std_user_id_fk 
foreign key(user_id) 
    references user(id)
    on delete set null;
