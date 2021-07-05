CREATE TABLE users
(
  id serial NOT NULL,
  nick text NOT NULL,
  inserted_datetime timestamp with time zone DEFAULT now(),
  active boolean NOT NULL DEFAULT FALSE,
  age integer,
  height_cm double precision,
  phones bigint[],
  PRIMARY KEY (id)
);

CREATE TABLE departments
(
  id serial NOT NULL,
  name text NOT NULL,
  rooms text[] NOT NULL,
  active boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY (id)
);

CREATE TABLE user_departments
(
  id serial NOT NULL,
  user_id integer NOT NULL,
  department_id integer NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments (id) ON UPDATE RESTRICT ON DELETE CASCADE
);

INSERT INTO users(nick, inserted_datetime, active, age, height_cm, phones) VALUES
('Bob', '2020-01-01 09:00:00', TRUE, 45, 178.2, ARRAY[200300, 487412]), -- ID: 1
('Brandon', '2020-01-02 12:05:00', TRUE, 24, 180.4, NULL), -- ID: 2
('Steve', '2020-01-02 12:05:00', FALSE, 41, 168.0, NULL), -- ID: 3
('Monica', '2020-01-03 13:10:00', TRUE, 36, 175.7, NULL), -- ID: 4
('Ingrid', '2020-01-04 14:15:00', TRUE, 18, 168.2, ARRAY[805305]); -- ID: 5

INSERT INTO departments(name, rooms, active) VALUES
('IT', ARRAY['A103', 'B201', 'B202'], TRUE), -- ID: 1
('HR', ARRAY['A101', 'A102'], TRUE), -- ID: 2
('Sales', ARRAY['B210'], TRUE), -- ID: 3
('Drivers', ARRAY[]::text[], FALSE); -- ID: 4

INSERT INTO user_departments(user_id, department_id) VALUES
(1, 1), -- ID: 1
(2, 1), -- ID: 2
(5, 1), -- ID: 3
(2, 2), -- ID: 4
(3, 2), -- ID: 5
(1, 3), -- ID: 6
(4, 3); -- ID: 7
