import sys
import itertools
import MySQLdb

offset = 2
maxPlayers = 10
maxGames = 20

class DbData:
    def __init__(self, playerNames, gameNames, ranks):
        self.playerNames = playerNames
        self.gameNames = gameNames
        self.ranks = ranks
        self.playerCount = len(playerNames)
        self.gameCount = len(gameNames)

class GameGroup:
    def __init__(self, games, groups):
        self.games = games
        self.groups = groups
        
    def htmlString(self, playerNames, gameNames):
        result = ""
        for (group, game) in zip(self.groups, self.games):
            for player in group:
                result += playerNames[player] + ", "
            result += " play " + gameNames[game] + "<br />"
        return result + "<br />"

def rank(dbData):
    playersExceptFirst = range(1, dbData.playerCount)
    group1Options = itertools.combinations(playersExceptFirst, (dbData.playerCount / 2) - 1)
    if (dbData.playerCount % 2 == 1):
        group1Options = itertools.chain(group1Options, itertools.combinations(playersExceptFirst, (dbData.playerCount + 1) / 2 - 1))
    # group1 plus player 0 yields all possibilities for game 1 players.
    
    gamePairs = list(itertools.product(range(0, dbData.gameCount), repeat=2))
    
    result = ""
    scoreToGameGroups = {}
    playersExceptFirstSet = frozenset(playersExceptFirst)
    for group1 in group1Options:
        group1 = [0] + list(group1)
        group2 = tuple(playersExceptFirstSet.difference(group1))
        for pair in gamePairs:
            groupScore = score(GameGroup(pair, [group1, group2]), dbData.ranks, dbData.gameCount)
            if groupScore not in scoreToGameGroups:
                scoreToGameGroups[groupScore] = []
            scoreToGameGroups[groupScore].append(GameGroup(pair, (group1, group2)))
    
    sortedScores = sorted(scoreToGameGroups.keys())
    
    for index, adjective in enumerate(["Best", "Second-best", "Third-best"]):
        result += adjective + " score: " + str(normalize(sortedScores[index], dbData.playerCount)) + "<br />"
        for gameGroup in scoreToGameGroups[sortedScores[index]]:
            result += gameGroup.htmlString(dbData.playerNames, dbData.gameNames)
        
    return result

def score(gameGroup, rankData, gameCount):
    score = 0
    for (group, game) in zip(gameGroup.groups, gameGroup.games):
        for player in group:
            score += (offset + rankData[(player * gameCount) + game]) ** 2
    return score

# Normalizes scores such that a first-place choice is 0 and a second-place choice is 1
# Also formats and returns a string
def normalize(score, playerCount):
    totalOffset = offset ** 2 * playerCount
    offsetDivisor = (offset + 1.0) ** 2 - offset ** 2
    normalizedScore = (score - totalOffset) / offsetDivisor
    return ('%.1f' % normalizedScore).rstrip('0').rstrip('.')

if __name__ == "__main__":
    server = sys.argv[1]
    dbName = sys.argv[2]
    user = sys.argv[3]
    password = sys.argv[4]
    sessionId = sys.argv[5]

    db = MySQLdb.connect(host=server, user=user, passwd=password, db=dbName)
    cursor = db.cursor()

    #cursor.execute("SELECT count(*) FROM player WHERE session_id=%s", sessionId)
    #playerCount = cursor.fetchone()[0];
    
    #cursor.execute("SELECT count(*) FROM game WHERE session_id=%s", sessionId)
    #gameCount = cursor.fetchone()[0];
    
    cursor.execute("SELECT name FROM player WHERE session_id=%s ORDER BY ordinal", sessionId)
    playerNamesData = map(lambda t: t[0], cursor.fetchall())
    
    cursor.execute("SELECT name FROM game WHERE session_id=%s ORDER BY ordinal", sessionId)
    gameNamesData = map(lambda t: t[0], cursor.fetchall())
    
    cursor.execute("SELECT rank FROM rank WHERE session_id=%s ORDER BY player, game", sessionId)
    ranksData = map(lambda t: t[0], cursor.fetchall())
    
    dbData = DbData(playerNamesData, gameNamesData, ranksData)
    
    cursor.close()
    db.close()
    
    if dbData.playerCount > maxPlayers:
        print("Too many players.")
    elif dbData.gameCount > maxGames:
        print("Too many games.")
    else:
        print(rank(dbData))