# Generate RSS 2.0 feed for ArsLexis news.xml RSS feed
# Requires PyRSS2Gen module (http://www.dalkescientific.com/Python/PyRSS2Gen.html)
# Author: Krzysztof Kowalczyk
# Possible future improvements:
#  - add ability to retain only N latest posts
import types,sys,os,string,time
import PyRSS2Gen as rssgen

RSS_SOURCE_DATA = "news.txt"

# RSS_OUT_FILE_NAME = "news.xml"
RSS_OUT_FILE_NAME = "news.xml"

FEED_TITLE = "ArsLexis news"
FEED_LINK = "http://www.arslexis.com/news.html"
FEED_DSC = "Latest news asbout ArsLexis software"

(ST_START, ST_AFTER_ID, ST_AFTER_TITLE, ST_AFTER_DATE, ST_AFTER_BODY) = range(5)

(LT_UNKNOWN, LT_ID, LT_TITLE, LT_DATE, LT_BODY) = range(5)

g_foundIds = []

# that is seriously brain-dead and slow implementation
def getTimeForYMD(year,month,day):
    secsInADay = 60*60*24
    curSecs = time.time()
    while True:
        curTime = time.gmtime(curSecs)
        curYear = curTime[0]
        curMonth = curTime[1]
        curDay = curTime[2]
        if year > curYear:
            print "desired date (y=%d,m=%d,d=%d) is greater than current date (y=%d,m=%d,d=%d)" % (year,month,day, curYear, curMonth, curDay)
            sys.exit(0)
        if year < curYear:
            curSecs -= secsInADay
            continue
        # years are the same

        if month > curMonth:
            print "desired date (y=%d,m=%d,d=%d) is greater than current date (y=%d,m=%d,d=%d)" % (year,month,day, curYear, curMonth, curDay)
            sys.exit(0)
        if month < curMonth:
            curSecs -= secsInADay
            continue
        # months are the same

        if day > curDay:
            print "desired date (y=%d,m=%d,d=%d) is greater than current date (y=%d,m=%d,d=%d)" % (year,month,day, curYear, curMonth, curDay)
            sys.exit(0)
        if day < curDay:
            curSecs -= secsInADay
            continue
        # years are the same
        t = time.gmtime(curSecs)
        tMidnight = (t[0], t[1], t[2], 0, 0, 0, t[6], t[7], t[8])
        return tMidnight

def fEmptyLine(l):
    l = string.strip(l)
    return 0 == len(l)

def fCommentLine(l):
    return 0==string.find(l,"#")

def genRssItem(itemId, itemTitle, itemDate, itemBody):
    itemGuid = rssgen.Guid("http://www.arslexis.com/news/news.html#" + itemId)

    rssItem = rssgen.RSSItem(
       title = itemTitle,
       link = "http://www.arslexis.com/news.html",
       guid = itemGuid,
       description = itemBody,
       pubDate = itemDate )
    return rssItem

def parseLine(line):
    if 0 == string.find(line,"@id:"):
        content = string.strip(line[4:])
        return (LT_ID, content)
    if 0 == string.find(line,"@title:"):
        content = string.strip(line[7:])
        return (LT_TITLE, content)
    if 0 == string.find(line,"@date:"):
        content = string.strip(line[6:])
        return (LT_DATE,content)
    if 0 == string.find(line,"@body"):
        return (LT_BODY, "")
    return (LT_UNKNOWN, "")

def parseId(line):
    (lineType, lineContent) = parseLine(line)
    assert LT_ID == lineType
    return lineContent

def parseTitle(line):
    (lineType, lineContent) = parseLine(line)
    assert LT_TITLE == lineType
    return lineContent

def parseDate(line):
    (lineType, lineContent) = parseLine(line)
    assert LT_DATE == lineType
    (year,month,day) = string.split(lineContent,"-")
    year = int(year)
    assert year >= 2003 and year <=2020
    month = int(month)
    assert month>=1 and month<=12
    day = int(day)
    assert day>=1 and day<=31
    date = getTimeForYMD(year,month,day)
    return date

def assertIsBody(line):
    (lineType,lineContent) = parseLine(line)
    assert LT_BODY == lineType

def loadRssItems(file):
    global g_foundIds

    fo = open(file,"r")
    items = []

    state = ST_START
    title = None

    for line in fo:

        if fCommentLine(line) and not state == ST_AFTER_BODY:
            continue

        if fEmptyLine(line) and not state == ST_AFTER_BODY:
            # we ignore empty lines everywhere except as part of the body
            continue

        if ST_START == state:
            articleId = parseId(line)
            assert articleId not in g_foundIds # article ids should be unique
            g_foundIds.append(articleId)
            state = ST_AFTER_ID
            continue

        if ST_AFTER_ID == state:
            title = parseTitle(line)
            state = ST_AFTER_TITLE
            continue

        if ST_AFTER_TITLE == state:
            date = parseDate(line)
            state = ST_AFTER_DATE
            continue

        if ST_AFTER_DATE == state:
            assertIsBody(line)
            state = ST_AFTER_BODY
            body = ""
            continue

        if ST_AFTER_BODY == state:
            (lineType,lineContent) = parseLine(line)
            if lineType == LT_UNKNOWN:
                # this is another line of the body, so just remember it
                body += line
                continue
            # we only expect a new item which must start with id
            assert LT_ID == lineType
            # this is a new article, create an article based on accumulated info
            newItem = genRssItem( articleId, title, date, body)
            items.append(newItem)
            articleId = parseId(line)
            assert articleId not in g_foundIds
            g_foundIds.append(articleId)
            state = ST_AFTER_ID
            continue

    fo.close()

    assert ST_AFTER_BODY == state
    newItem = genRssItem( articleId, title, date, body)
    items.append(newItem)
    return items

def main():
    file = RSS_SOURCE_DATA  # default input file
    if len(sys.argv)>2:
        print "Usage: genRSSfeed.py [input_rss_file.txt]"
        sys.exit(0)
    if 2==len(sys.argv)==2:
        file = sys.argv[1]
    # load the source file
    rss = rssgen.RSS2(
         title = FEED_TITLE,
         link = FEED_LINK,
         description = FEED_DSC,
         lastBuildDate = time.gmtime(),
         language = "en-US")
    rss.items = loadRssItems(file)
    rss.write_xml(open(RSS_OUT_FILE_NAME,"w"))

main()
