import sys
import html
import itertools
import collections
import mariadb
import time

# An offset of 1.5 means 5 2nd places = 3 1st + 2 3rd places.
offset = 1.5
maxPlayers = 15
maxGames = 12
maxTables = 3

class DbData:
    def __init__(self, playerNames, gameNames, tableCount, penalties):
        self.playerNames = playerNames
        self.gameNames = gameNames
        self.tableCount = tableCount
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
        minPlayersPerGame = len(playerNames) // len(self.groups)
            
        result = ""
        for (game, players) in gamesToPlayers.items():
            for player in players:
                result += html.escape(playerNames[player]) + ", "
            if len(players) > 1 + minPlayersPerGame:
                result += "all "
            result += "play <span class=\"gamename\">" + html.escape(gameNames[game]) + "</span>"
            if len(players) > 1 + minPlayersPerGame:
                result += " (" + str(len(players) // minPlayersPerGame) + " games)"
            result += "<br />"
        return result + "<br />"

def rank(dbData):
    if dbData.tableCount == 1:
        return rankOneTable(dbData)
    elif dbData.tableCount == 2:
        return rankTwoTables(dbData)
    else:
        return rankMultiTable(dbData)
    
def rankOneTable(dbData):
    scoreToGameGroups = dict()
    group = range(0, dbData.playerCount)
    for game in range(0, dbData.gameCount):
        groupScore = score(GameGroup([game], [group]), dbData.penalties, dbData.gameCount)
        if groupScore not in scoreToGameGroups:
            scoreToGameGroups[groupScore] = []
        scoreToGameGroups[groupScore].append(GameGroup([game], [group]))
        
    return getHtmlResult(scoreToGameGroups)

def rankTwoTables(dbData):
    gamePairs = list(itertools.product(range(0, dbData.gameCount), repeat=2))
    
    scoreToGameGroups = dict()
    for pair in gamePairs:
        for (group1, group2) in playerCombinationsForTwoTables(dbData.playerCount):
            groupScore = score(GameGroup(pair, [group1, group2]), dbData.penalties, dbData.gameCount)
            if groupScore not in scoreToGameGroups:
                scoreToGameGroups[groupScore] = []
            scoreToGameGroups[groupScore].append(GameGroup(pair, [group1, group2]))
            
            # If the games are the same, all groups will score the same.
            if pair[0] == pair[1]:
                break
            
    return getHtmlResult(scoreToGameGroups)

def rankMultiTable(dbData):
    # Performance optimization which can affect non-best results
    bestGamesByGroup = getBestGamesByGroup(dbData.playerCount, dbData.tableCount, dbData.penalties, dbData.gameCount)
    
    scoreToGameGroups = dict()
    combinationStartTime = time.time()
    combinations = playerCombinationsForNTables(dbData.playerCount, dbData.tableCount)
    if test:
        print("Time to find " + str(len(combinations)) + " combinations: " + str(time.time() - combinationStartTime))
    
    scoreStartTime = time.time()
    for combination in combinations:
        score = sum(bestGamesByGroup[group][0][1] for group in combination)
        scores = scoreToGameGroups.setdefault(score, []) # mutable list
        # Each group could have several "best" games. Use itertools.product to find all combinations of best games, and add a GameGroup for each.
        scores.extend([GameGroup(games, combination) for games in itertools.product(*[[bg[0] for bg in bestGamesByGroup[group]] for group in combination])])
    if test:
        print("Time to find scores: " + str(time.time() - scoreStartTime))
    
    return getHtmlResult(scoreToGameGroups)

def getHtmlResult(scoreToGameGroups):
    sortedScores = sorted(scoreToGameGroups.keys())
    # Map top games to who plays them
    result = ""
    for topScore, adjective in zip(sortedScores[:3], ["Best", "Second-best", "Third-best"]):
        result += adjective + " score: " + str(normalize(topScore, dbData.playerCount)) + "<br />"
        for gameGroup in removeDuplicates(scoreToGameGroups[topScore]):
            result += gameGroup.htmlString(dbData.playerNames, dbData.gameNames)
    return result

# After we find a group containing multiples of a particular game, remove all subsequent groups that have the same bag of games.
# This avoids outputting too many identical results for a game played at multiple tables.
def removeDuplicates(sameScoreGameGroups):
    result = []
    gameBagsToSkip = []
    for gameGroup in sameScoreGameGroups:
        gameBag = collections.Counter(gameGroup.games)
        if gameBag not in gameBagsToSkip:
            if gameBag.most_common(1)[0][1] > 1:
                gameBagsToSkip.append(gameBag)
            result.append(gameGroup)
    return result

# Return an iterator over sorted player tuples
def singleGroupCombinations(playerCount, tableCount):
    players = range(0, playerCount)
    (playersPerGroup, extraPlayers) = divmod(playerCount, tableCount)
    groupOptions = itertools.combinations(players, playersPerGroup)
    if extraPlayers > 0:
        groupOptions = itertools.chain(groupOptions, itertools.combinations(players, playersPerGroup+1))
    return groupOptions
    
# Return a dictionary of sorted player tuples to a list of (best game, best score) tuples
# For performance, filters to the ones with the best score (which could affect non-best results)
def getBestGamesByGroup(playerCount, tableCount, penalties, gameCount):
    result = dict()
    games = range(0, gameCount)
    for group in singleGroupCombinations(playerCount, tableCount):
        scores = [scoreOneGame(group, g, penalties, gameCount) for g in games]
        bestScore = min(scores)
        result[group] = [s for s in enumerate(scores) if s[1] == bestScore]
    return result

# Return an iterator over pairs of player lists
def playerCombinationsForTwoTables(playerCount):
    playersExceptFirst = range(1, playerCount)
    group1Options = itertools.combinations(playersExceptFirst, (playerCount // 2) - 1)
    if (playerCount % 2 == 1):
        group1Options = itertools.chain(group1Options, itertools.combinations(playersExceptFirst, (playerCount + 1) // 2 - 1))
    # group1 plus player 0 yields all possibilities for game 1 players.
    playersExceptFirstSet = frozenset(playersExceptFirst)
    return map(lambda group1: ([0] + list(group1), list(playersExceptFirstSet.difference(group1))), group1Options)

# Returns a list of tableCount-sized lists of player tuples
# Rather slow due to recursion. Should try caching results in files at least for n >= 4, since there aren't too many input combinations.
def playerCombinationsForNTables(playerCount, tableCount):
    maxGroupSize = (playerCount + tableCount - 1) // tableCount
    maxGroupsOfMaxSize = tableCount if playerCount % tableCount == 0 else playerCount % tableCount
    return playerCombinationsForNTablesRecursive(playerCount, tableCount, maxGroupSize, maxGroupsOfMaxSize)

# Returns a list of tableCount-sized lists of player tuples
def playerCombinationsForNTablesRecursive(playerCount, tableCount, maxGroupSize, maxGroupsOfMaxSize):
    if playerCount == 0:
        return [[() for i in range(tableCount)]]
        
    result = []
    smaller = playerCombinationsForNTablesRecursive(playerCount-1, tableCount, maxGroupSize, maxGroupsOfMaxSize)
    for combination in smaller:
        result.extend(waysToAddPlayer(combination, playerCount-1, maxGroupSize, maxGroupsOfMaxSize))
    return result
    
# combination is a list of tuples of players
# Returns a list of distinct ways to create a new combination by adding the new player (list of tuples)
def waysToAddPlayer(combination, newPlayer, maxGroupSize, maxGroupsOfMaxSize):
    result = []
    groupsOfMaxSize = len([g for g in combination if len(g) == maxGroupSize])
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
    test = sys.argv[6] if len(sys.argv) > 6 else False

    db = mariadb.connect(host=server, user=user, password=password, database=dbName, port=3306)
    cursor = db.cursor()
    
    cursor.execute("SELECT name FROM player WHERE session_id=? ORDER BY ordinal", (sessionId,))
    playerNamesData = [t[0] for t in cursor.fetchall()]
    
    cursor.execute("SELECT tables FROM session WHERE id=?", (sessionId,))
    tableCount = cursor.fetchone()[0] if not test else int(test)
    
    cursor.execute("SELECT name FROM game WHERE session_id=? ORDER BY ordinal", (sessionId,))
    gameNamesData = [t[0] for t in cursor.fetchall()]
    
    cursor.execute("SELECT rank FROM rank WHERE session_id=? ORDER BY player, game", (sessionId,))
    # Precompute the penalty for each player playing each game
    penaltyData = [(offset + t[0]) ** 2 for t in cursor.fetchall()]
    
    dbData = DbData(playerNamesData, gameNamesData, tableCount, penaltyData)
    
    cursor.close()
    db.close()
    
    if dbData.playerCount > maxPlayers:
        print("Too many players.")
    elif dbData.gameCount > maxGames:
        print("Too many games.")
    elif dbData.tableCount > maxTables and not test:
        print("Too many tables.")
    else:
        startTime = time.time()
        print(rank(dbData))
        if test:
            print("Elapsed time: " + str(time.time() - startTime))