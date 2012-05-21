import sys, os, getopt

config_file = ''
sys.path.append(os.path.dirname(sys.argv[0]) + '/modules')

import opts
opts.printHeader()

def main():
    lArgs, config_file = opts.parseOptions()    
    try:
        import processor
        processor.process(lArgs, config_file)
    except Exception, msg:
        print 'ERROR (%s): %s' % (msg.__class__.__name__, msg)        
        sys.exit(2)
        
if __name__ == "__main__":
    main()