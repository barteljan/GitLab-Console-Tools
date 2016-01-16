Some tools for using GitLab on the console.
Contains a command to clone all repos from a specific GitLab group.

##Use it like that##

###Clone all repos of a specific group###
```
php console.php gitlab:clone:group -p"/path/to/your/gitlab" -g"YourGitlabGroup" -t"YourApiToken"
```