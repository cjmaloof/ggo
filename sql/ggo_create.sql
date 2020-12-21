create table session (
    id int AUTO_INCREMENT,
    label varchar(255),
    created DATETIME,
    PRIMARY KEY (id),
    INDEX (label(13))
);

create table player (
    session_id int,
    name varchar(255),
    ordinal tinyint,
    PRIMARY KEY (session_id, ordinal),
    FOREIGN KEY (session_id) REFERENCES session(id) ON DELETE CASCADE
);

create table game (
    session_id int REFERENCES session(id),
    name varchar(255),
    ordinal tinyint,
    PRIMARY KEY (session_id, ordinal),
    FOREIGN KEY (session_id) REFERENCES session(id) ON DELETE CASCADE
);

create table rank (
    session_id int,
    player tinyint,
    game tinyint,
    rank tinyint,
    PRIMARY KEY (session_id, player, game),
    FOREIGN KEY (session_id) REFERENCES session(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id, player) REFERENCES player(session_id, ordinal),
    FOREIGN KEY (session_id, game) REFERENCES game(session_id, ordinal)
);
