(function(d3){
    containerExplorer = function (classes, usedServices, my) {
        var config = {
            diameter: 900,
            treeWidth: 900,
            treeHeight: 300,
            apisearch: 'http://api.symfony.com/2.2/index.html?q='
        };

        classes.nodes.push({
            id:    'service_container',
            class: 'Symfony\\Component\\DependencyInjection\\Container'
        });

        var indexedNodes = {};
        classes.nodes.forEach(function(e) {
            e.used = -1 !== usedServices.indexOf(e['id']);
            indexedNodes[e.id] = e;
        });

        d3.map(config).forEach(function(d, i){
            if (typeof my[i] != 'undefined') {
                config[i] = my[i];
            }
        });

        var diameter = config.diameter,
            radius = diameter / 2,
            innerRadius = radius - 150,
            treeWidth  = config.treeWidth,
            treeHeight = config.treeHeight
        ;

        var diagonal = d3.svg.diagonal()
            .projection(function(d) { return [d.y, d.x]; })
        ;

        var cluster = d3.layout.cluster()
            .size([360, innerRadius])
            .sort(null)
            .value(function(d) { return d.size; });

        var bundle = d3.layout.bundle();

        var used      = usedServices.length,
            available = classes.nodes.length,
            payload   = used / available,
            unused    = []
        ;

        var packages = {
            // Construct the package hierarchy from class names.
            root: function(classes) {
              var map = {};

              function find(name, data) {
                  var node = map[name], i;
                  if (!node) {
                      node = map[name] = data || {
                          name: name,
                          children: []
                      };

                      if (name && name.length) {
                          i = name.lastIndexOf("\\");
                          if (i === -1) {
                              i = name.lastIndexOf("_");
                          }
                          node.parent = find(name.substring(0, i));
                          if (typeof node.parent.children == 'undefined') {
                              node.parent.children = [];
                          }
                          node.parent.children.push(node);
                          node.key = name.substring(i + 1);
                      }
                  }
                  return node;
              }

              classes.forEach(function(d) {
                  find(d['class'], d);
              });

              return map[""];
            },

            // Return a list of imports for the given array of nodes.
            imports: function(nodes, edges) {
              var map = {},
                  imports = [];

              // Compute a map from name to node.
              nodes.forEach(function(d) {
                  map[d.id] = d;
              });

              // For each import, construct a link from the source to target node.
              edges.forEach(function(d) {
                  if (typeof map[d[1]] == 'undefined' ) {
                      //console.log(d[0]);
                  } else if (typeof map[d[0]] == 'undefined') {
                      //console.log(d[1]);
                  } else {
                    imports.push({
                        source: map[d[0]],
                        target: map[d[1]]
                    });
                  }
              });

              return imports;
            }
        };

    function printClass(d)
    {
        if (typeof d.class !== 'undefined') {
            var ns     = d.class.split('\\'),
                vendor = ns[0];

            if (ns.length > 1) {
                return vendor + (ns.length > 2 ? '\\...\\' : '\\') + ns.slice(-1)[0]
            } else {
                return vendor;
            }
        }
    }

    function printService(d)
    {
        if (typeof d !== 'undefined' && typeof d.id !== 'undefined') {
            var ns   = d.id.split('.'),
                main = ns[0];

            if (ns.length > 1) {
                return main + (ns.length > 2 ? '...' : '.') + ns.slice(-1)[0]
            } else {
                return main;
            }
        }
    }

    /**
     * Builds a function which builds the
     * transitive closure of the relationship
     * "service A needs service B to initialize"
     *
     * @var reverse Whether we are building the inverse relationship
     */
    function buildClosure(inverse)
    {
        var origin = inverse ? 0 : 1;
        var dest   = 1 - origin;

        return function transitiveClosure (service, current) {
            if (typeof(current) === 'undefined') {
                var current = {
                    name: indexedNodes[service],
                    children: []
                };
            }

            classes.edges.forEach(function(e) {
                if (e[origin] === service) {
                    current.children.push(transitiveClosure(e[dest]));
                }
            });

            return current;
        }
    }

        function drawTree(closure)
        {
            var diameter = 600;

            var tree = d3.layout.tree()
                .size([360, diameter / 2 - 60])
                .separation(function(a, b) { return (a.parent == b.parent ? 1 : 2) / a.depth; });

            var nodes = tree.nodes(closure),
                links = tree.links(nodes);

            var diagonal = d3.svg.diagonal.radial()
                .projection(function(d) { return [d.y, d.x / 180 * Math.PI]; });

            var svg = d3.select("#container_tree").html('').append("svg")
                .attr("width", diameter)
                .attr("height", diameter)
              .append("g")
                .attr("transform", "translate(" + diameter / 2 + "," + diameter / 2 + ")");

            var link = svg.selectAll(".link")
                .data(links)
              .enter().append("path")
                .attr("class", "link")
                .attr("d", diagonal);

            var node = svg.selectAll(".node")
                .data(nodes)
              .enter().append("g")
                .attr("class", "node")
                .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")"; })

            node.append("circle")
                .attr("r", 4.5);

            node.append("text")
                .attr("dy", ".31em")
                .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
                .attr("transform", function(d) { return d.x < 180 ? "translate(8)" : "rotate(180)translate(-8)"; })
                .text(function(d) { return printService(d.name); });
        }

        var nodes   = cluster.nodes(packages.root(classes.nodes)),
            links   = packages.imports(nodes, classes.edges),
            splines = bundle(links)
        ;

        d3.select("#container_filter").html(d3.format('%')(payload) + ' initialized (' + used + ' / ' + classes.nodes.length + ') ');

        var svg = d3.select("#container_explorer").html('').append("svg")
            .attr("width", diameter)
            .attr("height", diameter)
          .append("g")
            .attr("transform", "translate(" + radius + "," + radius + ")")
        ;

        var line = d3.svg.line.radial()
            .interpolate("bundle")
            .tension(.85)
            .radius(function(d) { return d.y; })
            .angle(function(d) { return d.x / 180 * Math.PI; })
        ;

        function mouseover(d) {
            svg.selectAll("path.link.target-" + d.key)
              .classed("target", true)
              .each(updateNodes("source", true))
            ;

            svg.selectAll("path.link.source-" + d.key)
              .classed("source", true)
              .each(updateNodes("target", true))
            ;
        }

        function mouseout(d) {
            svg.selectAll("path.link.source-" + d.key)
              .classed("source", false)
              .each(updateNodes("target", false))
            ;

            svg.selectAll("path.link.target-" + d.key)
              .classed("target", false)
              .each(updateNodes("source", false))
            ;
        }

        function updateNodes(name, value) {
            return function(d) {
                if (value) this.parentNode.appendChild(this);
                svg.select("#node-" + d[name].key).classed(name, value);
            };
        }

        var path = svg.selectAll("path.link")
            .data(links)
          .enter().append("svg:path")
            .attr("class", function(d, i) {
                return "link source-" + d.source.key + " target-" + d.target.key;
            })
            .attr("d", function(d, i) {
                return line(splines[i]);
            });
        ;

        svg.selectAll(".node")
            .data(nodes)
            .enter().append("g")
              .attr("class", function (d) {return d.used ? 'node used': 'node'; })
              .attr("id", function(d) { return "node-" + d.key; })
              .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")"; })
            .append("text")
              .attr("dx", function(d) { return d.x < 180 ? 8 : -8; })
              .attr("dy", ".31em")
              .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
              .attr("transform", function(d) { return d.x < 180 ? null : "rotate(180)"; })
              .text(printClass)
              .on('mouseover', mouseover)
              .on('mouseout', mouseout)
              .on('dblclick', function(d) {
                  if (typeof d.class !== 'undefined') {
                      var vendor = d.class.split('\\')[0];
                      if (vendor === 'Symfony') {
                          window.open(config.apisearch + d.class, '_blank');
                      }
                  }
              })
              .on('click', function(d) {
                  drawTree({
                      name: d,
                      children: [
                          {
                              name: { id: 'NEEDS'},
                              children: buildClosure(true)(d.id).children
                          },
                          {
                              name: { id: 'IS NEEDED BY' },
                              children: buildClosure(false)(d.id).children
                          }
                      ]
                  });
              })
        ;

        d3.select("input[type=range]").on("change", function() {
            line.tension(this.value / 100);
            path.attr("d", function(d, i) { return line(splines[i]); });
        });
    }
})(d3);