import sys
import cgi
import itertools
import MySQLdb

offset = 2
maxPlayers = 10
maxGames = 20

class DbData:
    def __init__(self, playerNames, gameNames, penalties):
        self.playerNames = playerNames
        self.gameNames = gameNames
        self.penalties = penalties
        self.playerCount = len(playerNames)
        self.gameCount = len(gameNames)

class GameGroup:
    def __init__(self, games, groups):
        self.games = games
        self.groups = groups
        
    def htmlString(self, playerNames, gameNames):
        result = ""
        if self.games[0] == self.games[1]:
            for player in self.groups[0] + self.groups[1]:
                result += cgi.escape(playerNames[player], True) + ", "
            result += " all play " + cgi.escape(gameNames[self.games[0]], True) + "<br />"
        else:
            for (group, game) in zip(self.groups, self.games):
                for player in group:
                    result += cgi.escape(playerNames[player], True) + ", "
                result += " play " + cgi.escape(gameNames[game], True) + "<br />"
        return result + "<br />"

def rank(dbData):
    gamePairs = list(itertools.product(range(0, dbData.gameCount), repeat=2))
    
    result = ""
    scoreToGameGroups = {}
            
    for pair in gamePairs:
        for (group1, group2) in playerCombinationsForTwoGames(dbData.playerCount):
            groupScore = score(GameGroup(pair, [group1, group2]), dbData.penalties, dbData.gameCount)
            if groupScore not in scoreToGameGroups:
                scoreToGameGroups[groupScore] = []
            scoreToGameGroups[groupScore].append(GameGroup(pair, (group1, group2)))
            
            # If the games are the same, all groups will score the same.
            if pair[0] == pair[1]:
                break
            
    sortedScores = sorted(scoreToGameGroups.keys())
    
    for topScore, adjective in zip(sortedScores[:3], ["Best", "Second-best", "Third-best"]):
        result += adjective + " score: " + str(normalize(topScore, dbData.playerCount)) + "<br />"
        for gameGroup in scoreToGameGroups[topScore]:
            result += gameGroup.htmlString(dbData.playerNames, dbData.gameNames)
        
    return result

# Return an iterator over pairs of tuples
def playerCombinationsForTwoGames(playerCount):
    playersExceptFirst = range(1, playerCount)
    group1Options = itertools.combinations(playersExceptFirst, (playerCount / 2) - 1)
    if (dbData.playerCount % 2 == 1):
        group1Options = itertools.chain(group1Options, itertools.combinations(playersExceptFirst, (playerCount + 1) / 2 - 1))
    # group1 plus player 0 yields all possibilities for game 1 players.
    playersExceptFirstSet = frozenset(playersExceptFirst)
    return itertools.imap(lambda group1: ([0] + list(group1), list(playersExceptFirstSet.difference(group1))), group1Options)

# Compute the score for one set of groups matched with games
def score(gameGroup, penalties, gameCount):
    score = 0
    for group, game in zip(gameGroup.groups, gameGroup.games):
        score += scoreOneGame(group, game, penalties, gameCount)
    return score

def scoreOneGame(group, game, penalties, gameCount):
    score = 0
    for player in group:
        score += penalties[(player * gameCount) + game]
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
    
    cursor.execute("SELECT name FROM player WHERE session_id=%s ORDER BY ordinal", sessionId)
    playerNamesData = map(lambda t: t[0], cursor.fetchall())
    
    cursor.execute("SELECT name FROM game WHERE session_id=%s ORDER BY ordinal", sessionId)
    gameNamesData = map(lambda t: t[0], cursor.fetchall())
    
    cursor.execute("SELECT rank FROM rank WHERE session_id=%s ORDER BY player, game", sessionId)
    # Precompute the penalty for each player playing each game
    penaltyData = map(lambda t: (offset + t[0]) ** 2, cursor.fetchall())
    
    dbData = DbData(playerNamesData, gameNamesData, penaltyData)
    
    cursor.close()
    db.close()
    
    if dbData.playerCount > maxPlayers:
        print("Too many players.")
    elif dbData.gameCount > maxGames:
        print("Too many games.")
    else:
        print(rank(dbData))