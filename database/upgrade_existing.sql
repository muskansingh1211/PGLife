-- OPTIONAL ONE-TIME UPGRADE for a PGLife database imported from the ORIGINAL starter SQL.
-- Do NOT run this on a fresh database imported from database/pglife.sql,
-- because the fresh SQL already includes these changes.
-- Back up your database first. This expects existing user emails and
-- user/property interest pairs to be unique.

ALTER TABLE `users`
  MODIFY `password` varchar(255) NOT NULL,
  ADD UNIQUE KEY `uq_users_email` (`email`);

ALTER TABLE `interested_users_properties`
  ADD UNIQUE KEY `uq_user_property` (`user_id`, `property_id`);
