import sys
import cgi
import itertools
import collections
import MySQLdb
import time

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
        gamesToPlayers = dict()
        for (group, game) in zip(self.groups, self.games):
            gamesToPlayers.setdefault(game, []).extend(group)
            
        result = ""
        for (game, players) in gamesToPlayers.items():
            for player in players:
                result += cgi.escape(playerNames[player], True) + ", "
            if len(players) > 1 + len(playerNames) / len(self.groups):
                result += "all "
            result += "play " + cgi.escape(gameNames[game], True) + "<br />"
        return result + "<br />"

def rank(dbData):
    gamePairs = list(itertools.product(range(0, dbData.gameCount), repeat=2))
    
    result = ""
    scoreToGameGroups = dict()
            
    for pair in gamePairs:
        for (group1, group2) in playerCombinationsForTwoGroups(dbData.playerCount):
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

def rankMultiGroup(dbData, groupCount):
    bestGamesByGroup = getBestGamesByGroup(dbData.playerCount, groupCount, dbData.penalties, dbData.gameCount)
    
    result = ""
    scoreToGameGroups = dict()
    for combination in playerCombinationsForNGroups(dbData.playerCount, groupCount):
        score = sum(bestGamesByGroup[group][0][1] for group in combination)
        scores = scoreToGameGroups.setdefault(score, []) # mutable list
        for games in itertools.product(*[[bg[0] for bg in bestGamesByGroup[group]] for group in combination]):
            scores.append(GameGroup(games, combination))
        
    sortedScores = sorted(scoreToGameGroups.keys())
    
    # Map top games to who plays them
    for topScore, adjective in zip(sortedScores[:3], ["Best", "Second-best", "Third-best"]):
        result += adjective + " score: " + str(normalize(topScore, dbData.playerCount)) + "<br />"
        for gameGroup in removeDuplicates(scoreToGameGroups[topScore]):
            result += gameGroup.htmlString(dbData.playerNames, dbData.gameNames)
        
    return result
                
# After we find a group containing multiples of a particular game, remove all subsequent groups that have the same bag of games.
# Could theoretically over-remove in rare cases of 4+ games but it's probably fine in practice.
def removeDuplicates(sameScoreGameGroups):
    result = []
    gameBagsToSkip = []
    for gameGroup in sameScoreGameGroups:
        gameBag = collections.Counter(gameGroup.games)
        if gameBag not in gameBagsToSkip:
            gameBagsToSkip.append(gameBag)
            result.append(gameGroup)
    return result

# Return an iterator over sorted player tuples
def singleGroupCombinations(playerCount, groupCount):
    players = range(0, playerCount)
    (playersPerGroup, extraPlayers) = divmod(playerCount, groupCount)
    groupOptions = itertools.combinations(players, playersPerGroup)
    if extraPlayers > 0:
        groupOptions = itertools.chain(groupOptions, itertools.combinations(players, playersPerGroup+1))
    return groupOptions
    
# Return a dictionary of sorted player tuples to a list of (best game, best score) tuples
# For performance, filters to the ones with the best score (which could affect non-best results)
def getBestGamesByGroup(playerCount, groupCount, penalties, gameCount):
    result = dict()
    games = range(0, gameCount)
    for group in singleGroupCombinations(playerCount, groupCount):
        scores = [scoreOneGame(group, g, penalties, gameCount) for g in games]
        bestScore = min(scores)
        result[group] = filter(lambda s: s[1] == bestScore, enumerate(scores))
    return result

# Return an iterator over pairs of player lists
def playerCombinationsForTwoGroups(playerCount):
    playersExceptFirst = range(1, playerCount)
    group1Options = itertools.combinations(playersExceptFirst, (playerCount / 2) - 1)
    if (playerCount % 2 == 1):
        group1Options = itertools.chain(group1Options, itertools.combinations(playersExceptFirst, (playerCount + 1) / 2 - 1))
    # group1 plus player 0 yields all possibilities for game 1 players.
    playersExceptFirstSet = frozenset(playersExceptFirst)
    return itertools.imap(lambda group1: ([0] + list(group1), list(playersExceptFirstSet.difference(group1))), group1Options)

# Returns a list of groupCount-sized lists of player tuples
def playerCombinationsForNGroups(playerCount, groupCount):
    maxGroupSize = (playerCount + groupCount - 1) / groupCount
    maxGroupsOfMaxSize = groupCount if playerCount % groupCount == 0 else playerCount % groupCount
    return playerCombinationsForNGroupsRecursive(playerCount, groupCount, maxGroupSize, maxGroupsOfMaxSize)

# Returns a list of groupCount-sized lists of player tuples
def playerCombinationsForNGroupsRecursive(playerCount, groupCount, maxGroupSize, maxGroupsOfMaxSize):
    if playerCount == 0:
        return [[() for i in range(groupCount)]]
        
    result = []
    smaller = playerCombinationsForNGroupsRecursive(playerCount-1, groupCount, maxGroupSize, maxGroupsOfMaxSize)
    for combination in smaller:
        result.extend(waysToAddPlayer(combination, playerCount-1, maxGroupSize, maxGroupsOfMaxSize))
    return result
    
# combination is a list of tuples of players
# Returns a list of distinct ways to create a new combination by adding the new player (list of tuples)
def waysToAddPlayer(combination, newPlayer, maxGroupSize, maxGroupsOfMaxSize):
    result = []
    groupsOfMaxSize = len(filter(lambda g: len(g) == maxGroupSize, combination))
    for (i, group) in enumerate(combination):
        # Don't add to a group at max size, and don't create a new one if we have enough
        if len(group) < maxGroupSize and (groupsOfMaxSize < maxGroupsOfMaxSize or len(group) < maxGroupSize-1):
            subresult = list(combination)
            subresult[i] = subresult[i] + (newPlayer,)
            result.append(subresult)
            # Ensure that groups within a combination are ordered by leftmost element
            if len(group) == 0:
                break
    return result

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
        startTime = time.time()
        print(rank(dbData))
        print("Elapsed time: " + str(time.time() - startTime))